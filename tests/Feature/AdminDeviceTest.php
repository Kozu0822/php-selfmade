<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_device(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/devices', [
                'name' => 'iPhone SE 3rd',
            ])
            ->assertRedirect('/admin?tab=devices');

        $this->assertDatabaseHas('devices', [
            'name' => 'iPhone SE 3rd',
        ]);
    }

    public function test_admin_can_update_device_before_it_is_used(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone SE']);

        $this->actingAs($admin)
            ->post("/admin/devices/{$device->id}/update", [
                'name' => 'iPhone SE 3rd',
            ])
            ->assertRedirect('/admin?tab=devices');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'iPhone SE 3rd',
        ]);
    }

    public function test_admin_can_delete_device_before_it_is_used(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone SE']);

        $this->actingAs($admin)
            ->post("/admin/devices/{$device->id}/delete")
            ->assertRedirect('/admin?tab=devices');

        $this->assertSoftDeleted('devices', [
            'id' => $device->id,
        ]);
    }

    public function test_admin_can_update_device_when_it_has_related_data(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $device->symptoms()->attach($symptom->id);

        $this->actingAs($admin)
            ->post("/admin/devices/{$device->id}/update", [
                'name' => 'iPhone 13 mini',
            ])
            ->assertRedirect('/admin?tab=devices');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'iPhone 13 mini',
        ]);
    }

    public function test_admin_cannot_delete_device_when_it_has_related_data(): void
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
            ->post("/admin/devices/{$device->id}/delete")
            ->assertRedirect('/admin?tab=devices')
            ->assertSessionHasErrors('device');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'iPhone 13',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'device_id' => $device->id,
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
