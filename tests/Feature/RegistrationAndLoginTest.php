<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RegistrationAndLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试用户注册
     */
    public function testUserRegistration()
    {
        date_default_timezone_set('Asia/Shanghai');
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password
        ];

        $response = $this->post('/register', $data);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => $data['email']
        ]);
    }

    /**
     * 测试用户登录
     * @depends testUserRegistration
     */
    public function testUserLoginWithMfa()
    {
        $data = [
            'email' => $this->email,
            'password' => $this->password
        ];

        Mail::fake();

        $response = $this->post('/login', $data);

        $response->assertViewIs('auth.login');
        $response->assertViewHas('showMfa', true);

        $mfaToken = Session::get('mfa_token');

        $mfaData = [
            'email' => $this->email,
            'password' => $this->password,
            'mfa_token' => $mfaToken
        ];

        $mfaResponse = $this->post(route('mfa.verify'), $mfaData);

        $mfaResponse->assertRedirect(route('customers.index'));
        $this->assertTrue(Auth::check());
    }

}
