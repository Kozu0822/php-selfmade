<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $customer = User::create([
            'role' => 0,
            'name' => '山田 太郎',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '100-0001',
            'address' => '東京都千代田区千代田1-1',
            'phone_number' => '090-1234-5678',
        ]);

        $customers = [$customer];

        for ($i = 1; $i <= 12; $i++) {
            $customers[] = User::create([
                'role' => 0,
                'name' => '予約 顧客'.$i,
                'email' => 'customer'.$i.'@example.com',
                'password' => Hash::make('password'),
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-'.$i,
                'phone_number' => '090-1000-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        User::create([
            'role' => 1,
            'name' => '店舗管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // ----- 端末 -----
        $iphone15 = Device::create(['name' => 'iPhone 15 Pro']);
        $iphone13 = Device::create(['name' => 'iPhone 13']);
        $iphone17 = Device::create(['name' => 'iPhone 17']);
        $ipad = Device::create(['name' => 'iPad Air 5th']);
        $macbook = Device::create(['name' => 'MacBook Air']);

        // ----- 症状 -----
        $screen = Symptom::create(['name' => '画面割れ', 'description' => '画面パネルの破損や表示不良がある状態です。']);
        $battery = Symptom::create(['name' => 'バッテリー劣化', 'description' => '充電の減りが早い、電源が急に落ちる状態です。']);
        $keyboard = Symptom::create(['name' => 'キーボード不良', 'description' => 'キー入力が反応しない、または一部のキーが故障している状態です。']);
        $camera = Symptom::create(['name' => 'カメラ不良', 'description' => '写真がぼやける、カメラが起動しないなどの状態です。']);
        $charging = Symptom::create(['name' => '充電できない', 'description' => '充電ケーブルを挿しても充電されない状態です。']);
        $sound = Symptom::create(['name' => 'スピーカーの不具合', 'description' => '本体スピーカーから音が出ない、音が割れる、雑音が出る状態です。']);
        $power = Symptom::create(['name' => '電源が入らない', 'description' => '電源ボタンを押しても起動しない状態です。']);
        $back = Symptom::create(['name' => '背面ガラス割れ', 'description' => '本体背面のガラスが割れている状態です。']);
        $water = Symptom::create(['name' => '水没', 'description' => '水濡れや水没により正常に動作しない状態です。']);
        $consultation = Symptom::create(['name' => '来店相談', 'description' => '症状がはっきりしないため、店舗で確認する予約です。']);

        // ----- 端末ごとに選べる症状 -----
        $iphoneSymptoms = [$screen, $battery, $camera, $charging, $sound, $power, $back, $water, $consultation];
        foreach ([$iphone15, $iphone13, $iphone17] as $iphone) {
            $iphone->symptoms()->attach(collect($iphoneSymptoms)->pluck('id')->all());
        }
        $ipad->symptoms()->attach([$screen->id, $battery->id, $camera->id, $charging->id, $sound->id, $consultation->id]);
        $macbook->symptoms()->attach([$battery->id, $keyboard->id, $sound->id, $power->id, $consultation->id]);

        // ----- 端末専用の部品（画面・バッテリー・キーボード）-----
        $deviceParts = [
            ['device' => $iphone15, 'name' => 'iPhone 15 Pro 画面パネル', 'stock' => 8, 'symptom' => $screen],
            ['device' => $iphone13, 'name' => 'iPhone 13 画面パネル', 'stock' => 5, 'symptom' => $screen],
            ['device' => $iphone17, 'name' => 'iPhone 17 画面パネル', 'stock' => 3, 'symptom' => $screen],
            ['device' => $ipad, 'name' => 'iPad Air 5th 画面パネル', 'stock' => 6, 'symptom' => $screen],
            ['device' => $iphone15, 'name' => 'iPhone 15 Pro バッテリー', 'stock' => 7, 'symptom' => $battery],
            ['device' => $iphone13, 'name' => 'iPhone 13 バッテリー', 'stock' => 4, 'symptom' => $battery],
            ['device' => $iphone17, 'name' => 'iPhone 17 バッテリー', 'stock' => 1, 'symptom' => $battery],
            ['device' => $ipad, 'name' => 'iPad Air 5th バッテリー', 'stock' => 2, 'symptom' => $battery],
            ['device' => $macbook, 'name' => 'MacBook Air バッテリー', 'stock' => 5, 'symptom' => $battery],
            ['device' => $macbook, 'name' => 'MacBook Air キーボード', 'stock' => 9, 'symptom' => $keyboard],
        ];

        foreach ($deviceParts as $row) {
            $part = Part::create([
                'device_id' => $row['device']->id,
                'name' => $row['name'],
                'stock' => $row['stock'],
            ]);
            $part->symptoms()->attach($row['symptom']->id);
        }

        // ----- 共通部品（全機種共通：device_id = null）-----
        $commonParts = [
            ['name' => 'カメラモジュール', 'stock' => 20, 'symptom' => $camera],
            ['name' => '充電コネクタ', 'stock' => 20, 'symptom' => $charging],
            ['name' => 'スピーカー', 'stock' => 20, 'symptom' => $sound],
            ['name' => '基板（マザーボード）', 'stock' => 15, 'symptom' => $power],
            ['name' => '背面パネル', 'stock' => 12, 'symptom' => $back],
        ];

        foreach ($commonParts as $row) {
            $part = Part::create([
                'device_id' => null,
                'name' => $row['name'],
                'stock' => $row['stock'],
            ]);
            $part->symptoms()->attach($row['symptom']->id);
        }

        // 水没は基板を使う（電源が入らないと共通の部品を流用）
        $board = Part::where('name', '基板（マザーボード）')->first();
        $board->symptoms()->attach($water->id);

        // ----- 予約枠 -----
        $times = [
            [10, 0], [10, 20], [10, 40],
            [11, 0], [11, 20], [11, 40],
            [12, 0], [12, 20], [12, 40],
            [13, 0], [13, 20], [13, 40],
            [14, 0], [14, 20], [14, 40],
            [15, 0], [15, 20], [15, 40],
        ];

        $timeSlots = [];

        foreach ($times as $index => [$hour, $minute]) {
            $timeSlots[] = TimeSlot::create([
                'slot_at' => now()->addDays(intdiv($index, 6) + 1)->setTime($hour, $minute),
                'is_open' => $index !== 17,
                'is_reserved' => false,
            ]);
        }

        // ----- サンプル予約（一部はAI診断利用として入力文を保存）-----
        $devices = [$iphone15, $iphone13, $iphone17, $ipad, $macbook];
        $aiTexts = [
            '画面割れ' => '落としてしまい、画面が割れて表示がおかしいです。',
            'バッテリー劣化' => '充電してもすぐに電池が減ってしまいます。',
            'カメラ不良' => 'カメラを起動すると画面が真っ暗で写真が撮れません。',
            '充電できない' => 'ケーブルを挿しても充電のマークが出ません。',
            'スピーカーの不具合' => '音楽を再生しても本体スピーカーから音が出ません。',
        ];

        foreach (array_slice($timeSlots, 0, 12) as $index => $timeSlot) {
            $device = $devices[$index % count($devices)];
            $availableSymptoms = $device->symptoms()->get();
            $symptom = $availableSymptoms[$index % $availableSymptoms->count()];

            // 最初の5件は AI 診断利用の予約として入力文を保存
            $symptomText = ($index < 5 && isset($aiTexts[$symptom->name]))
                ? $aiTexts[$symptom->name]
                : null;

            $reservation = Reservation::create([
                'user_id' => $customers[$index]->id,
                'device_id' => $device->id,
                'symptom_id' => $symptom->id,
                'time_slot_id' => $timeSlot->id,
                'status' => 'pending',
                'symptom_text' => $symptomText,
            ]);

            $reservationParts = $symptom->parts()
                ->where(function ($query) use ($device) {
                    $query->whereNull('device_id')
                        ->orWhere('device_id', $device->id);
                })
                ->get();

            foreach ($reservationParts as $part) {
                $part->decrement('stock');
                $reservation->parts()->attach($part->id);
            }

            $timeSlot->update(['is_reserved' => true]);
        }
    }
}
