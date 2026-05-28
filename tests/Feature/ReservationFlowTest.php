<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Part;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_reservation(): void
    {
        $data = $this->prepareReservationData();

        $this->actingAs($data['user']);

        $this->post('/reservations/device', [
            'device_id' => $data['device']->id,
        ])->assertRedirect('/reservations/symptom');

        $this->post('/reservations/symptom', [
            'symptom_id' => $data['symptom']->id,
        ])->assertRedirect('/reservations/time');

        $this->post('/reservations/time', [
            'time_slot_id' => $data['timeSlot']->id,
        ])->assertRedirect('/reservations/confirm');

        $this->post('/reservations')->assertRedirect('/reservations/complete');

        $this->assertDatabaseHas('reservations', [
            'user_id' => $data['user']->id,
            'device_id' => $data['device']->id,
            'symptom_id' => $data['symptom']->id,
            'time_slot_id' => $data['timeSlot']->id,
            'status' => 'pending',
        ]);

        $this->assertSame(1, $data['part']->fresh()->stock);
        $this->assertTrue($data['timeSlot']->fresh()->is_reserved);
    }

    public function test_customer_can_cancel_reservation(): void
    {
        $data = $this->prepareReservationData();
        $this->actingAs($data['user']);

        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
            'reservation.time_slot_id' => $data['timeSlot']->id,
        ]);

        $this->post('/reservations');
        $reservation = $data['user']->reservations()->first();

        $this->post("/reservations/{$reservation->id}/cancel")
            ->assertRedirect('/mypage');

        $this->assertSame('cancelled_by_user', $reservation->fresh()->status);
        $this->assertSame(2, $data['part']->fresh()->stock);
        $this->assertFalse($data['timeSlot']->fresh()->is_reserved);
    }

    public function test_customer_can_cancel_no_show_reservation(): void
    {
        $data = $this->prepareReservationData();
        $this->actingAs($data['user']);

        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
            'reservation.time_slot_id' => $data['timeSlot']->id,
        ]);

        $this->post('/reservations');
        $reservation = $data['user']->reservations()->first();
        $reservation->update(['status' => 'no_show']);

        $this->post("/reservations/{$reservation->id}/cancel")
            ->assertRedirect('/mypage');

        $this->assertSame('cancelled_by_user', $reservation->fresh()->status);
        $this->assertSame(2, $data['part']->fresh()->stock);
        $this->assertFalse($data['timeSlot']->fresh()->is_reserved);
    }

    public function test_admin_can_cancel_reservation(): void
    {
        $data = $this->prepareReservationData();
        $admin = User::create([
            'role' => 1,
            'name' => '店舗管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($data['user']);
        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
            'reservation.time_slot_id' => $data['timeSlot']->id,
        ]);

        $this->post('/reservations');
        $reservation = $data['user']->reservations()->first();

        $this->actingAs($admin)
            ->post("/admin/reservations/{$reservation->id}/cancel")
            ->assertRedirect('/admin?tab=reservations');

        $this->assertSame('cancelled_by_admin', $reservation->fresh()->status);
        $this->assertSame(2, $data['part']->fresh()->stock);
        $this->assertFalse($data['timeSlot']->fresh()->is_reserved);
    }

    public function test_customer_cannot_select_symptom_when_parts_are_out_of_stock(): void
    {
        $data = $this->prepareReservationData();
        $data['part']->update(['stock' => 0]);

        $this->actingAs($data['user']);
        session(['reservation.device_id' => $data['device']->id]);

        $this->post('/reservations/symptom', [
            'symptom_id' => $data['symptom']->id,
        ])
            ->assertRedirect('/reservations/symptom')
            ->assertSessionHasErrors('symptom_id');
    }

    public function test_past_time_slot_is_not_shown_to_customer(): void
    {
        $data = $this->prepareReservationData();
        $pastTimeSlot = TimeSlot::create([
            'slot_at' => now()->subHour(),
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $this->actingAs($data['user']);
        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
        ]);

        $this->get('/reservations/time')
            ->assertOk()
            ->assertDontSee($pastTimeSlot->slot_at->format('Y年n月j日 H:i'))
            ->assertSee($data['timeSlot']->slot_at->format('Y年n月j日 H:i'));

        $this->assertFalse($pastTimeSlot->fresh()->is_open);
    }

    public function test_customer_cannot_submit_past_time_slot(): void
    {
        $data = $this->prepareReservationData();
        $pastTimeSlot = TimeSlot::create([
            'slot_at' => now()->subHour(),
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $this->actingAs($data['user']);
        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
        ]);

        $this->post('/reservations/time', [
            'time_slot_id' => $pastTimeSlot->id,
        ])
            ->assertRedirect('/reservations/time')
            ->assertSessionHasErrors('time_slot_id');

        $this->assertFalse($pastTimeSlot->fresh()->is_open);
    }

    public function test_customer_cannot_submit_past_time_slot_in_japan_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 14:00:00', 'Asia/Tokyo'));

        $data = $this->prepareReservationData();
        $pastTimeSlot = TimeSlot::create([
            'slot_at' => Carbon::parse('2026-05-28 12:20:00', 'Asia/Tokyo'),
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $this->actingAs($data['user']);
        session([
            'reservation.device_id' => $data['device']->id,
            'reservation.symptom_id' => $data['symptom']->id,
        ]);

        $this->post('/reservations/time', [
            'time_slot_id' => $pastTimeSlot->id,
        ])
            ->assertRedirect('/reservations/time')
            ->assertSessionHasErrors('time_slot_id');

        $this->assertFalse($pastTimeSlot->fresh()->is_open);

        Carbon::setTestNow();
    }

    public function test_ai_can_select_symptom_for_customer(): void
    {
        $data = $this->prepareReservationData();
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"symptom_id": '.$data['symptom']->id.'}'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($data['user']);
        session(['reservation.device_id' => $data['device']->id]);

        $this->post('/reservations/symptom/ai', [
            'symptom_text' => '電池の減りがとても早いです。',
        ])->assertRedirect('/reservations/time');

        $this->assertSame($data['symptom']->id, session('reservation.symptom_id'));
    }

    public function test_ai_cannot_select_out_of_stock_symptom(): void
    {
        $data = $this->prepareReservationData();
        $data['part']->update(['stock' => 0]);
        $otherSymptom = Symptom::create(['name' => '画面割れ']);
        $otherPart = Part::create([
            'device_id' => $data['device']->id,
            'name' => '画面パネル',
            'stock' => 3,
        ]);
        $data['device']->symptoms()->attach($otherSymptom->id);
        $otherPart->symptoms()->attach($otherSymptom->id);

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"symptom_id": '.$data['symptom']->id.'}'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($data['user']);
        session(['reservation.device_id' => $data['device']->id]);

        $this->post('/reservations/symptom/ai', [
            'symptom_text' => '電池の減りがとても早いです。',
        ])
            ->assertRedirect('/reservations/symptom')
            ->assertSessionHasErrors('symptom_text');

        $this->assertNull(session('reservation.symptom_id'));
    }

    public function test_ai_shows_advice_for_setting_issue(): void
    {
        $data = $this->prepareReservationData();
        $visitSymptom = Symptom::create(['name' => '来店相談']);
        $data['device']->symptoms()->attach($visitSymptom->id);

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"symptom_id": '.$visitSymptom->id.', "advice": "Apple IDのパスワード再設定ページから、登録済みのメールアドレスまたは電話番号を入力してください。画面の案内に沿って本人確認を行うと、パスワードを再設定できます。操作が難しい場合は、来店相談として予約してください。"}'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($data['user']);
        session(['reservation.device_id' => $data['device']->id]);

        $this->post('/reservations/symptom/ai', [
            'symptom_text' => 'Appleアカウントを忘れました。',
        ])
            ->assertRedirect('/reservations/symptom')
            ->assertSessionHas('ai_advice');

        $this->assertNull(session('reservation.symptom_id'));
        $this->get('/reservations/symptom')
            ->assertSee('AI診断結果')
            ->assertSee('Apple IDのパスワード再設定ページ');
    }

    private function prepareReservationData(): array
    {
        $user = User::create([
            'role' => 0,
            'name' => '山田 太郎',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '100-0001',
            'address' => '東京都千代田区千代田1-1',
            'phone_number' => '090-1234-5678',
        ]);

        $device = Device::create(['name' => 'iPhone 13 Pro']);
        $symptom = Symptom::create(['name' => 'バッテリー劣化']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => 'スマートフォン用バッテリー',
            'stock' => 2,
        ]);
        $timeSlot = TimeSlot::create([
            'slot_at' => now()->addDay()->setTime(12, 30),
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $device->symptoms()->attach($symptom->id);
        $part->symptoms()->attach($symptom->id);

        return compact('user', 'device', 'symptom', 'part', 'timeSlot');
    }
}
