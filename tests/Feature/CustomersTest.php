<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;

class CustomersTest extends TestCase
{
    use RefreshDatabase;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('Asia/Shanghai');
        $client = Client::where('password_client', 1)->first();
        if(empty($client)){
            Artisan::call('passport:client --password');
            $client = Client::where('password_client', 1)->first();
        }
        $data = [
            'grant_type' => 'password',
            'client_id' => (string) $client->id,
            'client_secret' => $client->secret,
            'username' => $this->email,
            'password' => $this->password,
            'scope' => '',
        ];

        $response = $this->postJson('/oauth/token',$data);
        $this->token = $response->json('access_token');
    }
    /**
     * Test the customers index page.
     *
     */
    public function testIndex()
    {

        $user = \App\Models\User::where('email', $this->email)->first();
        $this->actingAs($user);
        $response = $this->get(route('customers.index'));
        $response->assertStatus(200);
    }

    /**
     * 创建客户
     **/
    public function testCreate()
    {

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 30,
            'dob' => '1993-01-01',
            'email' => 'john.doe@qq.com'
        ];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson(route('customers.create'), $data);
        $response->assertStatus(201)
                 ->assertJson([
                     'code' => 0,
                     'data' => $data
                 ]);
        $this->assertDatabaseHas('customers', $data);
    }

     /**
     * 客户列表
     */
    public function testList()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson(route('customers.list'));
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'data'
                 ]);
    }

    public function testCreateWithValidationErrors()
    {
        $data = [
            'first_name' => '',
            'last_name' => '',
            'age' => '',
            'dob' => '',
            'email' => ''
        ];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson(route('customers.create'), $data);
        $response->assertStatus(422)
                 ->assertJson([
                     'code' => 1
                 ]);
    }

    public function testUpdate()
    {
        $customer = Customer::factory()->create();
        $data = [
            'first_name' => 'Updated John',
            'last_name' => 'Updated Doe',
            'age' => 31,
            'dob' => '1992-01-01',
            'email' => 'updated.john.doe@qq.com'
        ];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson(route('customers.update', $customer), $data);
        $response->assertStatus(200)
                 ->assertJson([
                     'code' => 0,
                     'data' => [
                         'first_name' => $data['first_name'],
                         'last_name' => $data['last_name'],
                         'age' => $data['age'],
                         'dob' => $data['dob'],
                         'email' => $data['email']
                     ]
                 ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'age' => $data['age'],
            'dob' => $data['dob'],
            'email' => $data['email']
        ]);
    }

    public function testDelete()
    {
        $customer = Customer::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson(route('customers.delete', $customer));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
