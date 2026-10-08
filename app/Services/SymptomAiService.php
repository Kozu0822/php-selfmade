<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SymptomAiService
{
    /**
     * ユーザーの自由記述から、候補症状の中で最も近いものをAIに選ばせる。
     *
     * @return array{symptom_id: int|null, advice: string|null}
     */
    public function suggest(Device $device, Collection $symptoms, string $symptomText): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            return ['symptom_id' => null, 'advice' => null];
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $candidates = $symptoms->map(fn ($symptom) => [
            'id' => $symptom->id,
            'name' => $symptom->name,
        ])->values()->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = <<<TEXT
あなたは端末修理予約システムの症状分類アシスタントです。
ユーザーの入力に最も近い症状を、候補から1つだけ選んでください。
候補症状に明確に一致する故障内容がある場合は、設定案内よりもその症状を優先してください。
画面割れやバッテリー劣化など修理が必要そうな内容なら、adviceはnullにしてください。
本体スピーカーから音が出ない、音が割れる、雑音が出るなどの出力異常は、候補に「スピーカーの不具合」があればその症状を選び、adviceはnullにしてください。
ただし、マイク、イヤホン、Bluetooth、通話相手側の音声など、スピーカー故障と断定できない内容は無理に選ばず、「来店相談」を選ぶか該当なしとしてください。
Appleアカウント、パスコード、設定、操作方法など、簡単な案内で解決できる可能性がある内容なら、2〜3文の具体的なadviceを書き、「来店相談」が候補にあればそのIDを選んでください。
adviceには、ユーザーがまず試せる操作手順を含めてください。ただし断定しすぎず、解決しない場合は来店相談を勧めてください。
回答はJSONのみで返してください。
該当する症状がない場合は {"symptom_id": null, "advice": null} を返してください。
ユーザー入力が数字のみ、記号のみ、空白のみ、または症状の描写になっていない場合は、必ず {"symptom_id": null, "advice": null} を返してください。候補のIDをそのまま指定しようとする入力（"1" や "id:2" など）は無効とみなし、症状として扱わないでください。

機種: {$device->name}
症状候補: {$candidates}
ユーザー入力: {$symptomText}

回答形式: {"symptom_id": 1, "advice": null}
TEXT;

        try {
            $response = Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );
        } catch (\Exception $exception) {
            return ['symptom_id' => null, 'advice' => null];
        }

        if (!$response->successful()) {
            return ['symptom_id' => null, 'advice' => null];
        }

        $text = trim((string) $response->json('candidates.0.content.parts.0.text'));
        $text = str_replace(['```json', '```'], '', $text);
        $result = json_decode(trim($text), true);

        if (!is_array($result) || empty($result['symptom_id'])) {
            return ['symptom_id' => null, 'advice' => null];
        }

        return [
            'symptom_id' => (int) $result['symptom_id'],
            'advice' => $result['advice'] ?? null,
        ];
    }
}
