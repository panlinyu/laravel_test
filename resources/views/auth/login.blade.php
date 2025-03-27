@include('layout.head')
<link rel="stylesheet" href="/static/admin/css/login.css" media="all">
<div class="container">
    <div class="main-body">
        <div class="login-main">
            <div class="login-top">
                <span>登录</span>
                <span class="bg1"></span>
                <span class="bg2"></span>
            </div>
            <form id="loginForm" class="layui-form login-bottom" action="{{ isset($showMfa) ? route('mfa.verify') : route('login.form') }}" method="POST">
                <div class="center">
                    @if (!isset($showMfa))
                        <div class="item">
                            <span class="icon icon-2"></span>
                            <input type="text" name="email" required placeholder="请输入登录邮箱" maxlength="24" value="{{ old('email', isset($email) ? $email : '') }}"/>
                        </div>
                        <div class="item">
                            <span class="icon icon-3"></span>
                            <input type="password" name="password" required placeholder="请输入密码" maxlength="20" value="{{ old('password', isset($password) ? $password : '') }}">
                            <span class="bind-password icon icon-4"></span>
                        </div>
                    @else
                        <div class="item">
                            <span class="icon icon-5"></span>
                            <input type="text" name="mfa_token" required placeholder="请输入 MFA 令牌" maxlength="6"/>
                        </div>
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="password" value="{{ $password }}">
                        <p>已发送 MFA 令牌到您的邮箱，请查收。</p>
                    @endif
                </div>
                @if (!isset($showMfa))
                    <div class="tip">
                        <a href="{{ route('register') }}" class="forget-password">去注册</a>
                    </div>
                @endif
                @csrf
                <div class="layui-form-item" style="text-align:center; width:100%;height:100%;margin:0px;">
                    <button type="submit" class="login-btn">{{ isset($showMfa) ? '验证 MFA 令牌' : '立即登录' }}</button>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger" style="text-align: center;padding:2px 0;">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('message'))
                    <div class="alert alert-success" style="text-align: center;padding:2px 0;">
                        {{ session('message') }}
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
<script>
    layui.use('layer', function(){
        var layer = layui.layer;
        var form = document.getElementById('loginForm');
        form.addEventListener('submit', function(e) {
            var loadingIndex = layer.load(1, {
                shade: [0.3,'#fff']
            });
            form.addEventListener('load', function() {
                layer.close(loadingIndex);
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.querySelector('input[name="password"]');
        const bindPasswordSpan = document.querySelector('.bind-password');

        bindPasswordSpan.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    });
</script>
@include('layout.foot')
