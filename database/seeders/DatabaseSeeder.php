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

        $iphone15 = Device::create(['name' => 'iPhone 15 Pro']);
        $iphone13 = Device::create(['name' => 'iPhone 13']);
        $iphone17 = Device::create(['name' => 'iPhone 17']);
        $ipad = Device::create(['name' => 'iPad Air 5th']);
        $macbook = Device::create(['name' => 'MacBook Air']);

        $screen = Symptom::create([
            'name' => '画面割れ',
            'description' => '画面パネルの破損や表示不良がある状態です。',
        ]);

        $battery = Symptom::create([
            'name' => 'バッテリー劣化',
            'description' => '充電の減りが早い、電源が急に落ちる状態です。',
        ]);

        $keyboard = Symptom::create([
            'name' => 'キーボード不良',
            'description' => 'キー入力が反応しない、または一部のキーが故障している状態です。',
        ]);

        $consultation = Symptom::create([
            'name' => '来店相談',
            'description' => '症状がはっきりしないため、店舗で確認する予約です。',
        ]);

        $iphone15->symptoms()->attach([$screen->id, $battery->id, $consultation->id]);
        $iphone13->symptoms()->attach([$screen->id, $battery->id, $consultation->id]);
        $iphone17->symptoms()->attach([$screen->id, $battery->id, $consultation->id]);
        $ipad->symptoms()->attach([$screen->id, $battery->id, $consultation->id]);
        $macbook->symptoms()->attach([$battery->id, $keyboard->id, $consultation->id]);

        $iphone15Screen = Part::create([
            'device_id' => $iphone15->id,
            'name' => 'iPhone 15 Pro 画面パネル',
            'stock' => 8,
        ]);

        $iphone13Screen = Part::create([
            'device_id' => $iphone13->id,
            'name' => 'iPhone 13 画面パネル',
            'stock' => 5,
        ]);

        $iphone17Screen = Part::create([
            'device_id' => $iphone17->id,
            'name' => 'iPhone 17 画面パネル',
            'stock' => 3,
        ]);

        $ipadScreen = Part::create([
            'device_id' => $ipad->id,
            'name' => 'iPad Air 5th 画面パネル',
            'stock' => 6,
        ]);

        $iphone15Battery = Part::create([
            'device_id' => $iphone15->id,
            'name' => 'iPhone 15 Pro バッテリー',
            'stock' => 7,
        ]);

        $iphone13Battery = Part::create([
            'device_id' => $iphone13->id,
            'name' => 'iPhone 13 バッテリー',
            'stock' => 4,
        ]);

        $iphone17Battery = Part::create([
            'device_id' => $iphone17->id,
            'name' => 'iPhone 17 バッテリー',
            'stock' => 1,
        ]);

        $ipadBattery = Part::create([
            'device_id' => $ipad->id,
            'name' => 'iPad Air 5th バッテリー',
            'stock' => 2,
        ]);

        $keyboardPart = Part::create([
            'device_id' => $macbook->id,
            'name' => 'MacBook Air キーボード',
            'stock' => 9,
        ]);

        $macbookBattery = Part::create([
            'device_id' => $macbook->id,
            'name' => 'MacBook Air バッテリー',
            'stock' => 5,
        ]);

        $iphone15Screen->symptoms()->attach($screen->id);
        $iphone13Screen->symptoms()->attach($screen->id);
        $iphone17Screen->symptoms()->attach($screen->id);
        $ipadScreen->symptoms()->attach($screen->id);
        $iphone15Battery->symptoms()->attach($battery->id);
        $iphone13Battery->symptoms()->attach($battery->id);
        $iphone17Battery->symptoms()->attach($battery->id);
        $ipadBattery->symptoms()->attach($battery->id);
        $macbookBattery->symptoms()->attach($battery->id);
        $keyboardPart->symptoms()->attach($keyboard->id);

        $devices = [$iphone15, $iphone13, $iphone17, $ipad, $macbook];
        $symptoms = [$screen, $battery, $keyboard, $consultation];
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

        foreach (array_slice($timeSlots, 0, 12) as $index => $timeSlot) {
            $device = $devices[$index % count($devices)];
            $availableSymptoms = $device->symptoms()->get();
            $symptom = $availableSymptoms[$index % $availableSymptoms->count()];

            $reservation = Reservation::create([
                'user_id' => $customers[$index]->id,
                'device_id' => $device->id,
                'symptom_id' => $symptom->id,
                'time_slot_id' => $timeSlot->id,
                'status' => 'pending',
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
