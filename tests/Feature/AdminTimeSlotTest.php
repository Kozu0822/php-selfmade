<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTimeSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_time_slot(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/time-slots', [
                'slot_date' => '2026-06-01',
                'slot_time' => '10:30',
            ])
            ->assertRedirect('/admin?tab=time_slots');

        $this->assertDatabaseHas('time_slots', [
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => false,
        ]);
    }

    public function test_admin_cannot_create_time_slot_before_business_hours(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/time-slots', [
                'slot_date' => '2026-06-01',
                'slot_time' => '09:00',
            ])
            ->assertSessionHasErrors('slot_at');
    }

    public function test_admin_cannot_create_time_slot_after_business_hours(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/time-slots', [
                'slot_date' => '2026-06-01',
                'slot_time' => '19:30',
            ])
            ->assertSessionHasErrors('slot_at');
    }

    public function test_admin_cannot_create_past_time_slot(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/time-slots', [
                'slot_date' => '2020-01-01',
                'slot_time' => '10:30',
            ])
            ->assertSessionHasErrors('slot_at');
    }

    public function test_admin_can_open_and_close_time_slot(): void
    {
        $admin = $this->createAdmin();
        $timeSlot = TimeSlot::create([
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $this->actingAs($admin)
            ->post("/admin/time-slots/{$timeSlot->id}/toggle")
            ->assertRedirect('/admin?tab=time_slots');

        $this->assertFalse($timeSlot->fresh()->is_open);

        $this->actingAs($admin)
            ->post("/admin/time-slots/{$timeSlot->id}/toggle")
            ->assertRedirect('/admin?tab=time_slots');

        $this->assertTrue($timeSlot->fresh()->is_open);
    }

    public function test_admin_can_delete_not_reserved_time_slot(): void
    {
        $admin = $this->createAdmin();
        $timeSlot = TimeSlot::create([
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => false,
        ]);

        $this->actingAs($admin)
            ->post("/admin/time-slots/{$timeSlot->id}/delete")
            ->assertRedirect('/admin?tab=time_slots');

        $this->assertSoftDeleted('time_slots', [
            'id' => $timeSlot->id,
        ]);
    }

    public function test_admin_cannot_delete_reserved_time_slot(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13 Pro']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $timeSlot = TimeSlot::create([
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => true,
        ]);

        Reservation::create([
            'user_id' => $customer->id,
            'device_id' => $device->id,
            'symptom_id' => $symptom->id,
            'time_slot_id' => $timeSlot->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/time-slots/{$timeSlot->id}/delete")
            ->assertRedirect('/admin?tab=time_slots');

        $this->assertDatabaseHas('time_slots', [
            'id' => $timeSlot->id,
        ]);
    }

    public function test_admin_reservations_are_ordered_by_status_and_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 14:00:00', 'Asia/Tokyo'));

        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);

        $futureEarly = $this->createReservation($this->createCustomerNamed('予約 顧客A', 'a@example.com'), $device, $symptom, '2026-05-28 15:00:00', 'pending');
        $futureLate = $this->createReservation($this->createCustomerNamed('予約 顧客B', 'b@example.com'), $device, $symptom, '2026-05-28 16:00:00', 'pending');
        $past = $this->createReservation($this->createCustomerNamed('予約 顧客C', 'c@example.com'), $device, $symptom, '2026-05-28 12:20:00', 'pending');
        $this->createReservation($this->createCustomerNamed('予約 顧客D', 'd@example.com'), $device, $symptom, '2026-05-28 11:00:00', 'cancelled_by_user');

        $this->actingAs($admin)
            ->get('/admin?tab=reservations')
            ->assertOk()
            ->assertSeeInOrder([
                '予約 顧客A',
                '予約 顧客B',
                '予約 顧客C',
                '予約 顧客D',
            ])
            ->assertSee('来店なし');

        $this->assertSame('no_show', $past->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_time_slot_page_shows_no_show_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 14:00:00', 'Asia/Tokyo'));

        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $this->createReservation($this->createCustomer(), $device, $symptom, '2026-05-28 12:20:00', 'pending');

        $this->actingAs($admin)
            ->get('/admin?tab=time_slots')
            ->assertOk()
            ->assertSee('来店なし')
            ->assertDontSee('予約済み');

        Carbon::setTestNow();
    }

    public function test_admin_can_cancel_no_show_reservation(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => 'iPhone 13 画面パネル',
            'stock' => 1,
        ]);
        $part->symptoms()->attach($symptom->id);
        $reservation = $this->createReservation($customer, $device, $symptom, '2026-05-28 12:20:00', 'no_show');
        $reservation->parts()->attach($part->id);

        $this->actingAs($admin)
            ->post("/admin/reservations/{$reservation->id}/cancel")
            ->assertRedirect('/admin?tab=reservations');

        $this->assertSame('cancelled_by_admin', $reservation->fresh()->status);
        $this->assertFalse($reservation->timeSlot->fresh()->is_reserved);
        $this->assertSame(2, $part->fresh()->stock);
    }

    public function test_admin_can_view_reservation_detail_modal_from_reservation_list(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => 'iPhone 13 画面パネル',
            'stock' => 3,
        ]);
        $reservation = $this->createReservation($customer, $device, $symptom, '2026-06-01 10:30:00', 'pending');
        $reservation->parts()->attach($part->id);

        $this->actingAs($admin)
            ->get('/admin?tab=reservations')
            ->assertOk()
            ->assertSee('詳細を見る')
            ->assertDontSee('キャンセル');

        $this->actingAs($admin)
            ->get('/admin?tab=reservations&reservation_id='.$reservation->id)
            ->assertOk()
            ->assertSee('予約詳細')
            ->assertSee($customer->name)
            ->assertSee($device->name)
            ->assertSee($symptom->name)
            ->assertSee('必要部品')
            ->assertSee($part->name)
            ->assertSee('予約中')
            ->assertSee('キャンセル');
    }

    private function createAdmin(): User
    {
        return User::create([
            'role' => 1,
            'name' => '店舗管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createCustomer(): User
    {
        return User::create([
            'role' => 0,
            'name' => '山田 太郎',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '100-0001',
            'address' => '東京都千代田区千代田1-1',
            'phone_number' => '090-1234-5678',
        ]);
    }

    private function createCustomerNamed(string $name, string $email): User
    {
        return User::create([
            'role' => 0,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'postal_code' => '100-0001',
            'address' => '東京都千代田区千代田1-1',
            'phone_number' => '090-1234-5678',
        ]);
    }

    private function createReservation(User $customer, Device $device, Symptom $symptom, string $slotAt, string $status): Reservation
    {
        $timeSlot = TimeSlot::create([
            'slot_at' => $slotAt,
            'is_open' => true,
            'is_reserved' => true,
        ]);

        return Reservation::create([
            'user_id' => $customer->id,
            'device_id' => $device->id,
            'symptom_id' => $symptom->id,
            'time_slot_id' => $timeSlot->id,
            'status' => $status,
        ]);
    }
}
