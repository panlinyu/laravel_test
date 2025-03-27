<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;

/**
 * 客户管理控制器
 *
 * 该控制器处理与客户相关的操作，包括列出客户、创建客户、更新客户和删除客户。
 */
class CustomerController extends Controller
{

    /**
     * 显示客户列表页面
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $email = $request->query('email');
        $password = $request->query('password');
        $client = Client::where('password_client', 1)->first();

        $data = [
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'email' => $email,
            'password' => $password
        ];

        return view('customers.index', $data);
    }

    /**
     * 获取客户列表
     *
     * 需要在请求头中添加 Authorization: Bearer <token> 进行身份验证。
     *
     * 根据关键词搜索客户，并以 JSON 格式返回客户列表。
     * @queryParam keyword string 用于搜索客户的关键词，可匹配客户的名字或姓氏。 Example: John
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $query = Customer::query();
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($query) use ($keyword) {
                $query->where('first_name', 'like', "%$keyword%")
                      ->orWhere('last_name', 'like', "%$keyword%");
            });
        }

        $customers = $query->get();
        return response()->json([
            'code' => 0,
            'data' => $customers
        ], 200);
    }

    /**
     * 创建新客户
     *
     * 需要在请求头中添加 Authorization: Bearer <token> 进行身份验证。
     *
     * 创建一个新的客户记录。请求参数需要包含客户的基本信息。
     * @bodyParam first_name string required 客户的名字，最大长度为 50。 Example: John
     * @bodyParam last_name string required 客户的姓氏，最大长度为 50。 Example: Doe
     * @bodyParam age integer required 客户的年龄，最大为 999。 Example: 30
     * @bodyParam dob string required 客户的出生日期，格式为日期。 Example: 1990-01-01
     * @bodyParam email string required 客户的电子邮件地址，最大长度为 100。 Example: john.doe@example.com
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'age' => 'required|integer|max:999',
            'dob' => 'required|date',
            'email' => 'required|email|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1,
                'errors' => $validator->errors()
            ], 422);
        }
        $customer = Customer::create($request->all());
        return response()->json([
            'code' => 0,
            'data' => $customer
        ], 201);
    }

    /**
     * 更新客户信息
     *
     * 需要在请求头中添加 Authorization: Bearer <token> 进行身份验证。
     *
     * 更新指定客户的信息。请求参数可以包含需要更新的客户信息。
     * @bodyParam first_name string 客户的名字，最大长度为 50。 Example: Jane
     * @bodyParam last_name string 客户的姓氏，最大长度为 50。 Example: Smith
     * @bodyParam age integer 客户的年龄，最大为 999。 Example: 35
     * @bodyParam dob string 客户的出生日期，格式为日期。 Example: 1985-05-15
     * @bodyParam email string 客户的电子邮件地址，最大长度为 100。 Example: jane.smith@example.com
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
    */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'age' => 'integer|max:999',
            'dob' => 'date',
            'email' => 'email|max:100'
        ]);

        $customer->update($request->all());

        return response()->json([
            'code' => 0,
            'data' => $customer
        ], 200);
    }

    /**
     * 删除客户
     *
     * 需要在请求头中添加 Authorization: Bearer <token> 进行身份验证。
     *
     * 删除指定的客户记录。
     * @bodyParam id int 客户ID
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
    */
    public function delete(Customer $customer)
    {
        $customer->delete();
        return response()->json([
            'code' => 0
        ], 204);
    }
}
