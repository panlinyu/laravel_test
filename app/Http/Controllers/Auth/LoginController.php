<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 登录控制器
 *
 * 该控制器处理用户登录相关的操作，包括验证用户凭证、发送 MFA 令牌和验证 MFA 令牌。
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = "/home";

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * 用户登录
     *
     * 验证用户的电子邮件和密码，如果验证通过，则发送 MFA 令牌到用户的电子邮件。
     * @bodyParam email string required 用户的电子邮件地址。 Example: user@example.com
     * @bodyParam password string required 用户的密码。 Example: password123
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect("/login")->withErrors($validator)->withInput();
        }

        if($this->validateCredentials($request)) {
            $mfaToken = Str::random(6);
            session(['mfa_token' => $mfaToken]);

            Mail::raw('您的 MFA 令牌是: ' . $mfaToken, function ($message) use ($request) {
                $message->to($request->email)->subject('MFA 令牌');
            });

            return view('auth.login', [
                'showMfa' => true,
                'email' => $request->email,
                'password' => $request->password
            ]);
        }

        return redirect("/login")->with('message', '用户名或密码错误');
    }

    /**
     * MFA 令牌验证
     *
     * 验证用户输入的 MFA 令牌，如果验证通过，则完成用户登录。
     * @bodyParam mfa_token string required 用户输入的 MFA 令牌。 Example: 123456
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mfaVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mfa_token' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect("/login")->withErrors($validator)->withInput();
        }

        if ($request->mfa_token === session('mfa_token')) {
            // MFA 令牌验证成功，执行真正的登录
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                session()->forget('mfa_token');
                session(['pwd' => $credentials['password']]);
                return redirect("/home")->with('message', '登录成功');
            }
        }

        return redirect("/login")->with('message', 'MFA 令牌验证失败');
    }

     /**
     * 用户注销
     *
     * 注销当前登录的用户，并将用户重定向到登录页面。
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }

    /**
     * 验证用户凭证
     *
     * 验证用户的电子邮件和密码是否正确。
     * @bodyParam email string required 用户的电子邮件地址。
     * @bodyParam password string required 用户的密码。
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function validateCredentials(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $isValid = Auth::validate($credentials);

        $user = User::where('email', $credentials['email'])->first();
        $isValid = $user && Hash::check($credentials['password'], $user->password);

        return  $isValid;
    }
}
