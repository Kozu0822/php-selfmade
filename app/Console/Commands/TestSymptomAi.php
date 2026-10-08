<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Symptom;
use App\Services\SymptomAiService;
use Illuminate\Console\Command;

class TestSymptomAi extends Command
{
    protected $signature = 'ai:test-symptom {--device=iPhone 15 Pro} {--sleep=200}';

    protected $description = 'AI症状診断の精度をテストし、結果をCSVに出力する';

    public function handle(SymptomAiService $ai): int
    {
        $cases = require database_path('ai_test_cases.php');

        $device = Device::where('name', $this->option('device'))->first();
        if (!$device) {
            $this->error('端末が見つかりません: '.$this->option('device'));
            return self::FAILURE;
        }

        $candidates = $device->symptoms()->orderBy('symptoms.id')->get();
        $this->info("端末: {$device->name} / 候補症状: {$candidates->count()}件 / テスト: ".count($cases).'件');
        $this->newLine();

        $rows = [];
        $counts = ['⭕' => 0, '🔺' => 0, '❌' => 0];
        $byCategory = [];
        $byScope = [];

        foreach ($cases as $i => $case) {
            $result = $ai->suggest($device, $candidates, $case['input']);
            $picked = $result['symptom_id'] ? optional(Symptom::find($result['symptom_id']))->name : null;
            $hasAdvice = !empty($result['advice']);

            $judge = $this->judge($case, $picked, $hasAdvice);
            $scope = $this->scopeLabel($case['category']);
            $counts[$judge]++;
            $byCategory[$case['category']][$judge] = ($byCategory[$case['category']][$judge] ?? 0) + 1;
            $byScope[$scope][$judge] = ($byScope[$scope][$judge] ?? 0) + 1;

            $rows[] = [
                $i + 1,
                $scope,
                $case['category'],
                $case['input'],
                $this->expectLabel($case),
                $picked ?? '(なし)',
                $hasAdvice ? 'あり' : 'なし',
                $judge,
                $this->judgeLabel($judge),
            ];

            $this->line(sprintf('%3d  %s  [%s] %s => %s', $i + 1, $judge, $case['category'], mb_strimwidth($case['input'], 0, 24, '…'), $picked ?? 'null'));

            usleep((int) $this->option('sleep') * 1000);
        }

        $paths = $this->writeFiles($rows, $counts, $byCategory, $byScope, $device->name);

        $total = count($cases);
        $this->newLine();
        $this->info('==== 結果サマリー ====');
        $this->line(sprintf('⭕ 正解 : %d 件 (%.1f%%)', $counts['⭕'], $counts['⭕'] / $total * 100));
        $this->line(sprintf('🔺 許容 : %d 件 (%.1f%%)', $counts['🔺'], $counts['🔺'] / $total * 100));
        $this->line(sprintf('❌ 不正解: %d 件 (%.1f%%)', $counts['❌'], $counts['❌'] / $total * 100));
        $this->line(sprintf('⭕＋🔺 合計: %.1f%%', ($counts['⭕'] + $counts['🔺']) / $total * 100));
        $this->newLine();
        $this->line('詳細CSV: '.$paths['detail']);
        $this->line('集計CSV: '.$paths['summary']);
        $this->line('報告書  : '.$paths['report']);

        return self::SUCCESS;
    }

    private function judge(array $case, ?string $picked, bool $hasAdvice): string
    {
        $expect = $case['expect'];
        $accept = $case['accept'] ?? [];

        if ($expect === 'REJECT') {
            if ($picked === null) {
                return '⭕';
            }
            return in_array($picked, $accept, true) ? '🔺' : '❌';
        }

        if ($expect === 'CONSULT') {
            if ($picked === '来店相談' || $hasAdvice) {
                return '⭕';
            }
            return in_array($picked, $accept, true) ? '🔺' : '❌';
        }

        if ($picked === $expect) {
            return '⭕';
        }
        return in_array($picked, $accept, true) ? '🔺' : '❌';
    }

    private function expectLabel(array $case): string
    {
        return match ($case['expect']) {
            'REJECT' => '症状として扱わない',
            'CONSULT' => '来店相談 / アドバイス',
            default => $case['expect'],
        };
    }

    private function judgeLabel(string $judge): string
    {
        return match ($judge) {
            '⭕' => '正解',
            '🔺' => '許容',
            '❌' => '不正解',
        };
    }

    private function scopeLabel(string $category): string
    {
        return in_array($category, ['複合症状', '境界'], true)
            ? '参考評価'
            : '主要評価';
    }

    private function writeFiles(
        array $rows,
        array $counts,
        array $byCategory,
        array $byScope,
        string $deviceName
    ): array {
        $detailPath = storage_path('app/ai_test_result.csv');
        $summaryPath = storage_path('app/ai_test_summary.csv');
        $reportPath = storage_path('app/ai_test_report.md');

        $fp = fopen($detailPath, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['No', '評価区分', 'カテゴリ', '入力文', '期待', 'AI判定症状', 'advice', '結果', '判定']);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $summaryRows = [];
        foreach ($byScope as $scope => $scopeCounts) {
            $summaryRows[] = $this->summaryRow('評価区分', $scope, $scopeCounts);
        }
        foreach ($byCategory as $category => $categoryCounts) {
            $summaryRows[] = $this->summaryRow('カテゴリ', $category, $categoryCounts);
        }
        $summaryRows[] = $this->summaryRow('全体', '全70件', $counts);

        $fp = fopen($summaryPath, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['集計単位', '対象', '件数', '正解', '許容', '不正解', '正解率', '正解＋許容率']);
        foreach ($summaryRows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        file_put_contents(
            $reportPath,
            $this->buildReport($summaryRows, $deviceName)
        );

        return [
            'detail' => $detailPath,
            'summary' => $summaryPath,
            'report' => $reportPath,
        ];
    }

    private function summaryRow(string $unit, string $target, array $counts): array
    {
        $correct = $counts['⭕'] ?? 0;
        $acceptable = $counts['🔺'] ?? 0;
        $incorrect = $counts['❌'] ?? 0;
        $total = $correct + $acceptable + $incorrect;

        return [
            $unit,
            $target,
            $total,
            $correct,
            $acceptable,
            $incorrect,
            sprintf('%.1f%%', $total > 0 ? $correct / $total * 100 : 0),
            sprintf('%.1f%%', $total > 0 ? ($correct + $acceptable) / $total * 100 : 0),
        ];
    }

    private function buildReport(array $summaryRows, string $deviceName): string
    {
        $lines = [
            '# AI症状診断 精度テスト結果',
            '',
            '## テスト条件',
            '',
            '- 実施日時: '.now()->format('Y年m月d日 H:i'),
            '- 対象端末: '.$deviceName,
            '- AIモデル: '.config('services.gemini.model', 'gemini-2.5-flash'),
            '- temperature: 0',
            '- テスト件数: 70件',
            '- 主要評価: 典型表現、口語表現、非修理相談、無意味入力',
            '- 参考評価: 複合症状、英語・短文・長文などの境界入力',
            '',
            '## 集計結果',
            '',
            '| 集計単位 | 対象 | 件数 | 正解 | 許容 | 不正解 | 正解率 | 正解＋許容率 |',
            '|---|---|---:|---:|---:|---:|---:|---:|',
        ];

        foreach ($summaryRows as $row) {
            $lines[] = '| '.implode(' | ', $row).' |';
        }

        $lines = array_merge($lines, [
            '',
            '## 評価方針',
            '',
            '- 本システムは1件の予約につき1つの症状を選択する設計です。',
            '- そのため、複数症状を含む入力は通常の単一症状分類とは分けて参考評価としました。',
            '- 「スピーカーの不具合」は、本体スピーカーから音が出ない・音割れ・雑音など、出力側の故障に限定して評価しています。',
            '- マイク、Bluetoothイヤホン、通話相手側など原因を断定できない入力は、来店相談または手動選択への誘導を正しい動作とします。',
            '',
            '## エラー時の補助',
            '',
            '- AIが症状を特定できない場合は、利用者が症状一覧から手動で選択できます。',
            '- AI相談内容は予約データに保存し、管理者が予約一覧から確認できます。',
            '- AIの判定後も、在庫不足や選択対象外の症状はLaravel側で再確認し、予約登録を防止します。',
            '',
        ]);

        return implode(PHP_EOL, $lines);
    }
}
