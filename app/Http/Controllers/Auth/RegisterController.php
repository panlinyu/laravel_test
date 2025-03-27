<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * 注册控制器
 *
 * 该控制器处理用户注册相关的操作，包括验证用户输入和创建新用户记录。
 */
class RegisterController extends Controller
{
    protected $redirectTo = '/login';

    /**
     * 用户注册
     *
     * 验证用户输入的注册信息，如果验证通过，则创建新的用户记录。
     * @bodyParam name string required 用户的姓名，最大长度为 255。 Example: John Doe
     * @bodyParam email string required 用户的电子邮件地址，最大长度为 255，且必须唯一。 Example: user@example.com
     * @bodyParam password string required 用户的密码，最小长度为 6，且需要确认。 Example: password123
     * @bodyParam password_confirmation string required 用户确认的密码，必须与 password 一致。 Example: password123
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
    */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect("register")->withErrors($validator)->withInput();
        }
       User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect('/login')->with('success', 'Registration successful. Please login.');
    }

}
