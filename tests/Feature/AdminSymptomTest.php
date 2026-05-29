<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSymptomTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_symptom(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);

        $this->actingAs($admin)
            ->post('/admin/symptoms', [
                'name' => 'カメラ故障',
                'device_ids' => [$device->id],
            ])
            ->assertRedirect('/admin?tab=symptoms');

        $symptom = Symptom::where('name', 'カメラ故障')->first();

        $this->assertDatabaseHas('symptoms', [
            'name' => 'カメラ故障',
        ]);
        $this->assertTrue($symptom->devices()->where('devices.id', $device->id)->exists());
    }

    public function test_admin_can_update_symptom(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $symptom->devices()->attach($device->id);

        $this->actingAs($admin)
            ->post("/admin/symptoms/{$symptom->id}/update", [
                'name' => '画面割れ修理',
                'device_ids' => [$device->id],
            ])
            ->assertRedirect('/admin?tab=symptoms');

        $this->assertDatabaseHas('symptoms', [
            'id' => $symptom->id,
            'name' => '画面割れ修理',
        ]);
    }

    public function test_admin_can_disable_symptom_when_no_active_reservation_exists(): void
    {
        $admin = $this->createAdmin();
        $symptom = Symptom::create(['name' => '画面割れ']);

        $this->actingAs($admin)
            ->post("/admin/symptoms/{$symptom->id}/delete")
            ->assertRedirect('/admin?tab=symptoms');

        $this->assertSoftDeleted('symptoms', [
            'id' => $symptom->id,
        ]);
    }

    public function test_admin_cannot_disable_symptom_when_active_reservation_exists(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
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
            ->post("/admin/symptoms/{$symptom->id}/delete")
            ->assertRedirect('/admin?tab=symptoms')
            ->assertSessionHasErrors('symptom');

        $this->assertDatabaseHas('symptoms', [
            'id' => $symptom->id,
            'deleted_at' => null,
        ]);
    }

    public function test_disabled_symptom_is_not_shown_to_customer(): void
    {
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $device->symptoms()->attach($symptom->id);
        $symptom->delete();

        $this->actingAs($customer);
        session(['reservation.device_id' => $device->id]);

        $this->get('/reservations/symptom')
            ->assertOk()
            ->assertDontSee('画面割れ');
    }

    public function test_admin_can_restore_disabled_symptom(): void
    {
        $admin = $this->createAdmin();
        $symptom = Symptom::create(['name' => '画面割れ']);
        $symptom->delete();

        $this->actingAs($admin)
            ->post("/admin/symptoms/{$symptom->id}/restore")
            ->assertRedirect('/admin?tab=symptoms');

        $this->assertDatabaseHas('symptoms', [
            'id' => $symptom->id,
            'deleted_at' => null,
        ]);
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
}
