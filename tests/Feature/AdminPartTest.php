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

class AdminPartTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_part(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);

        $this->actingAs($admin)
            ->post('/admin/parts', [
                'device_id' => $device->id,
                'name' => 'iPhone 13 画面パネル',
                'stock' => 5,
                'symptom_ids' => [$symptom->id],
            ])
            ->assertRedirect('/admin?tab=parts');

        $part = Part::where('name', 'iPhone 13 画面パネル')->first();

        $this->assertDatabaseHas('parts', [
            'device_id' => $device->id,
            'name' => 'iPhone 13 画面パネル',
            'stock' => 5,
        ]);
        $this->assertTrue($part->symptoms()->where('symptoms.id', $symptom->id)->exists());
    }

    public function test_admin_can_update_part(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => '画面パネル',
            'stock' => 1,
        ]);

        $this->actingAs($admin)
            ->post("/admin/parts/{$part->id}/update", [
                'device_id' => $device->id,
                'name' => 'iPhone 13 画面パネル',
                'stock' => 4,
                'symptom_ids' => [$symptom->id],
            ])
            ->assertRedirect('/admin?tab=parts');

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'name' => 'iPhone 13 画面パネル',
            'stock' => 4,
        ]);
        $this->assertTrue($part->fresh()->symptoms()->where('symptoms.id', $symptom->id)->exists());
    }

    public function test_admin_cannot_select_visit_consultation_for_part(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '来店相談']);

        $this->actingAs($admin)
            ->post('/admin/parts', [
                'device_id' => $device->id,
                'name' => '相談用部品',
                'stock' => 1,
                'symptom_ids' => [$symptom->id],
            ])
            ->assertRedirect('/admin?tab=parts&add_part=1')
            ->assertSessionHasErrors('symptom_ids');

        $this->assertDatabaseMissing('parts', [
            'name' => '相談用部品',
        ]);
    }

    public function test_admin_can_disable_part_before_it_is_used_in_active_reservation(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => '画面パネル',
            'stock' => 1,
        ]);

        $this->actingAs($admin)
            ->post("/admin/parts/{$part->id}/delete")
            ->assertRedirect('/admin?tab=parts');

        $this->assertSoftDeleted('parts', [
            'id' => $part->id,
        ]);
    }

    public function test_admin_cannot_disable_part_used_in_active_reservation(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => '画面パネル',
            'stock' => 1,
        ]);
        $timeSlot = TimeSlot::create([
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => true,
        ]);
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'device_id' => $device->id,
            'symptom_id' => $symptom->id,
            'time_slot_id' => $timeSlot->id,
            'status' => 'pending',
        ]);
        $reservation->parts()->attach($part->id);

        $this->actingAs($admin)
            ->post("/admin/parts/{$part->id}/delete")
            ->assertRedirect('/admin?tab=parts')
            ->assertSessionHasErrors('part');

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_disable_part_used_only_in_cancelled_reservation(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $device = Device::create(['name' => 'iPhone 13']);
        $symptom = Symptom::create(['name' => '画面割れ']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => '画面パネル',
            'stock' => 1,
        ]);
        $timeSlot = TimeSlot::create([
            'slot_at' => '2026-06-01 10:30:00',
            'is_open' => true,
            'is_reserved' => false,
        ]);
        $reservation = Reservation::create([
            'user_id' => $customer->id,
            'device_id' => $device->id,
            'symptom_id' => $symptom->id,
            'time_slot_id' => $timeSlot->id,
            'status' => 'cancelled_by_user',
        ]);
        $reservation->parts()->attach($part->id);

        $this->actingAs($admin)
            ->post("/admin/parts/{$part->id}/delete")
            ->assertRedirect('/admin?tab=parts');

        $this->assertSoftDeleted('parts', [
            'id' => $part->id,
        ]);
    }

    public function test_admin_can_restore_disabled_part(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create(['name' => 'iPhone 13']);
        $part = Part::create([
            'device_id' => $device->id,
            'name' => '画面パネル',
            'stock' => 1,
        ]);
        $part->delete();

        $this->actingAs($admin)
            ->post("/admin/parts/{$part->id}/restore")
            ->assertRedirect('/admin?tab=parts');

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
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
