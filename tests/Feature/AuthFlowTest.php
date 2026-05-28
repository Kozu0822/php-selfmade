<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->post('/register/complete', [
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
            'action' => 'complete',
        ]);

        $response->assertOk();
        $response->assertSee('登録完了');

        $this->assertDatabaseHas('users', [
            'email' => 'hanako@example.com',
            'role' => 0,
            'postal_code' => '150-0001',
        ]);
    }

    public function test_customer_cannot_register_when_password_confirmation_is_different(): void
    {
        $response = $this->post('/register/confirm', [
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_customer_cannot_register_with_same_email(): void
    {
        User::create([
            'role' => 0,
            'name' => '既存ユーザー',
            'email' => 'hanako@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response = $this->post('/register/confirm', [
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_same_email_is_checked_before_confirm_page(): void
    {
        User::create([
            'role' => 0,
            'name' => '既存ユーザー',
            'email' => 'used@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response = $this->from('/register')->post('/register/confirm', [
            'name' => '佐藤 花子',
            'email' => 'used@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
    }

    public function test_same_email_on_complete_shows_email_error_not_password_error(): void
    {
        User::create([
            'role' => 0,
            'name' => '既存ユーザー',
            'email' => 'used@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
        ]);

        $response = $this->post('/register/complete', [
            'name' => '佐藤 花子',
            'email' => 'used@example.com',
            'password' => 'password',
            'postal_code' => '150-0001',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '090-1111-2222',
            'action' => 'complete',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $response->assertSessionDoesntHaveErrors('password');
    }

    public function test_register_confirm_get_redirects_to_register(): void
    {
        $response = $this->get('/register/confirm');

        $response->assertRedirect('/register');
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        User::create([
            'role' => 1,
            'name' => '店舗管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_login_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_postal_code_and_phone_number_must_be_half_width_numbers(): void
    {
        $response = $this->post('/register/confirm', [
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'postal_code' => '１２３-４５６７',
            'address' => '東京都渋谷区神宮前1-1',
            'phone_number' => '０９０-１１１１-２２２２',
        ]);

        $response->assertSessionHasErrors(['postal_code', 'phone_number']);
    }
}
