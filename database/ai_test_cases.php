<?php

/**
 * AI症状診断の精度テスト用ケース（70件）。
 *
 * expect:
 *   症状名      … その症状が選ばれれば正解
 *   'CONSULT'  … 来店相談が選ばれる、またはadviceが返れば正解
 *   'REJECT'   … symptom_idがnullなら正解
 *
 * accept:
 *   複合症状などで、別の妥当な候補として許容する症状名
 *
 * 主要評価:
 *   典型表現、口語表現、非修理相談、無意味入力
 *
 * 参考評価:
 *   複合症状、境界入力
 *
 * 端末は、候補症状が最も多いiPhone 15 Proで統一する。
 */

return [
    // ---- 典型表現（20件）----
    ['category' => '典型表現', 'input' => '画面が割れてしまいました', 'expect' => '画面割れ'],
    ['category' => '典型表現', 'input' => '液晶画面にひびが入っています', 'expect' => '画面割れ'],
    ['category' => '典型表現', 'input' => 'バッテリーの減りがとても早いです', 'expect' => 'バッテリー劣化'],
    ['category' => '典型表現', 'input' => '満充電にしても半日もちません', 'expect' => 'バッテリー劣化'],
    ['category' => '典型表現', 'input' => 'カメラのピントが合わず写真がぼやけます', 'expect' => 'カメラ不良'],
    ['category' => '典型表現', 'input' => 'カメラを起動しても画面が真っ暗です', 'expect' => 'カメラ不良'],
    ['category' => '典型表現', 'input' => '充電ケーブルを挿しても充電されません', 'expect' => '充電できない'],
    ['category' => '典型表現', 'input' => '充電マークが表示されず充電できません', 'expect' => '充電できない'],
    ['category' => '典型表現', 'input' => '本体スピーカーから音が出ません', 'expect' => 'スピーカーの不具合'],
    ['category' => '典型表現', 'input' => 'スピーカーの音が割れて聞き取りにくいです', 'expect' => 'スピーカーの不具合'],
    ['category' => '典型表現', 'input' => '動画を再生しても本体スピーカーが無音です', 'expect' => 'スピーカーの不具合'],
    ['category' => '典型表現', 'input' => '電源ボタンを押しても起動しません', 'expect' => '電源が入らない'],
    ['category' => '典型表現', 'input' => '充電はありますが電源が入りません', 'expect' => '電源が入らない'],
    ['category' => '典型表現', 'input' => '背面のガラスが割れています', 'expect' => '背面ガラス割れ'],
    ['category' => '典型表現', 'input' => '本体裏側のガラスにひびがあります', 'expect' => '背面ガラス割れ'],
    ['category' => '典型表現', 'input' => '端末を水に落としてしまいました', 'expect' => '水没'],
    ['category' => '典型表現', 'input' => 'ジュースをこぼして端末が濡れました', 'expect' => '水没'],
    ['category' => '典型表現', 'input' => '落下後、画面全体にひびが入りました', 'expect' => '画面割れ'],
    ['category' => '典型表現', 'input' => '購入から二年経ち、電池の持ちが悪くなりました', 'expect' => 'バッテリー劣化'],
    ['category' => '典型表現', 'input' => 'カメラアプリを開くとフリーズします', 'expect' => 'カメラ不良'],

    // ---- 口語表現（20件）----
    ['category' => '口語表現', 'input' => '落としたら画面がバキバキになった', 'expect' => '画面割れ'],
    ['category' => '口語表現', 'input' => '画面パリッといっちゃいました', 'expect' => '画面割れ'],
    ['category' => '口語表現', 'input' => 'なんか電池の減りがやばいんだけど', 'expect' => 'バッテリー劣化'],
    ['category' => '口語表現', 'input' => '最近バッテリーが全然もたない', 'expect' => 'バッテリー劣化'],
    ['category' => '口語表現', 'input' => '写真撮ろうとしたら真っ暗なんですけど', 'expect' => 'カメラ不良'],
    ['category' => '口語表現', 'input' => 'カメラぼけぼけで使い物にならない', 'expect' => 'カメラ不良'],
    ['category' => '口語表現', 'input' => 'ケーブル挿しても全然反応しない', 'expect' => '充電できない'],
    ['category' => '口語表現', 'input' => '朝から全く充電たまらない', 'expect' => '充電できない'],
    ['category' => '口語表現', 'input' => 'スピーカーが急に鳴らなくなった', 'expect' => 'スピーカーの不具合'],
    ['category' => '口語表現', 'input' => '本体から出る音がずっとビリビリしてる', 'expect' => 'スピーカーの不具合'],
    ['category' => '口語表現', 'input' => '音量上げてもスピーカーが無音なんだけど', 'expect' => 'スピーカーの不具合'],
    ['category' => '口語表現', 'input' => 'うんともすんとも言わなくなった', 'expect' => '電源が入らない', 'accept' => ['来店相談']],
    ['category' => '口語表現', 'input' => 'いきなり電源落ちてつかなくなった', 'expect' => '電源が入らない', 'accept' => ['バッテリー劣化']],
    ['category' => '口語表現', 'input' => '裏面がバキバキでケースがはまらない', 'expect' => '背面ガラス割れ'],
    ['category' => '口語表現', 'input' => '後ろのガラスがパリッといきました', 'expect' => '背面ガラス割れ'],
    ['category' => '口語表現', 'input' => 'トイレに落としちゃって動きがおかしい', 'expect' => '水没'],
    ['category' => '口語表現', 'input' => '雨でびしょ濡れになってから調子悪い', 'expect' => '水没'],
    ['category' => '口語表現', 'input' => '充電マークが全然出ないんです', 'expect' => '充電できない'],
    ['category' => '口語表現', 'input' => '電池がへたってきた感じがする', 'expect' => 'バッテリー劣化'],
    ['category' => '口語表現', 'input' => 'ボタン押しても真っ暗なままです', 'expect' => '電源が入らない'],

    // ---- 複合症状（5件・参考評価）----
    ['category' => '複合症状', 'input' => '画面も割れてるし電池もすぐ切れる', 'expect' => '画面割れ', 'accept' => ['バッテリー劣化']],
    ['category' => '複合症状', 'input' => '水没してからカメラもおかしい', 'expect' => '水没', 'accept' => ['カメラ不良']],
    ['category' => '複合症状', 'input' => '充電できないし電源も入らない', 'expect' => '充電できない', 'accept' => ['電源が入らない']],
    ['category' => '複合症状', 'input' => '画面割れと背面割れの両方です', 'expect' => '画面割れ', 'accept' => ['背面ガラス割れ']],
    ['category' => '複合症状', 'input' => 'スピーカーから音が出ず、充電もできません', 'expect' => 'スピーカーの不具合', 'accept' => ['充電できない']],

    // ---- 非修理相談（12件）----
    ['category' => '非修理相談', 'input' => 'パスコードを忘れてしまいました', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'Apple IDのパスワードが分かりません', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'Wi-Fiにつながらないのですが', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'アプリの使い方を教えてほしい', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => '機種変更のデータ移行をしたい', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => '設定の方法が分からない', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => '画面ロックの解除ができません', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'Bluetoothイヤホンから音が出ません', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => '通話相手に自分の声が届きません', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'iCloudのバックアップについて聞きたい', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => 'どんな修理ができるか相談したい', 'expect' => 'CONSULT'],
    ['category' => '非修理相談', 'input' => '症状がよく分からないので見てほしい', 'expect' => 'CONSULT'],

    // ---- 無意味入力（5件）----
    ['category' => '無意味入力', 'input' => 'あああああ', 'expect' => 'REJECT'],
    ['category' => '無意味入力', 'input' => 'asdfghjkl', 'expect' => 'REJECT'],
    ['category' => '無意味入力', 'input' => 'テストテスト', 'expect' => 'REJECT', 'accept' => ['来店相談']],
    ['category' => '無意味入力', 'input' => '今日はいい天気ですね', 'expect' => 'REJECT', 'accept' => ['来店相談']],
    ['category' => '無意味入力', 'input' => 'ピザが食べたい', 'expect' => 'REJECT'],

    // ---- 境界入力（8件・参考評価）----
    ['category' => '境界', 'input' => 'screen is broken', 'expect' => '画面割れ'],
    ['category' => '境界', 'input' => 'battery drains fast', 'expect' => 'バッテリー劣化'],
    ['category' => '境界', 'input' => 'no sound from the built-in speaker', 'expect' => 'スピーカーの不具合'],
    ['category' => '境界', 'input' => '割れた', 'expect' => '画面割れ', 'accept' => ['背面ガラス割れ']],
    ['category' => '境界', 'input' => '電池', 'expect' => 'バッテリー劣化', 'accept' => ['来店相談']],
    ['category' => '境界', 'input' => 'カメラ', 'expect' => 'カメラ不良', 'accept' => ['来店相談']],
    ['category' => '境界', 'input' => '水没した', 'expect' => '水没'],
    ['category' => '境界', 'input' => '昨日端末を床に落としてしまい、画面全体にひびが入り、表示の一部も見えなくなりました。修理をお願いしたいです。', 'expect' => '画面割れ'],
];
