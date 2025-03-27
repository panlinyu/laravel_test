@include('layout.head')
<link rel="stylesheet" href="/static/admin/css/login.css" media="all">
{{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
<div class="container">
    <div class="main-body">
        <div class="login-main">
            <div class="login-top">
                <span>注册</span>
                <span class="bg1"></span>
                <span class="bg2"></span>
            </div>
            <form class="layui-form login-bottom" action="{{ route('register.form') }}" method="POST">
                <div class="center">
                    <div class="item">
                        <span class="icon icon-2"></span>
                        <input type="text" name="name" required placeholder="请输入用户名" maxlength="24"/>
                    </div>
                    <div class="item">
                        <span class="icon icon-2"></span>
                        <input type="email" name="email" required placeholder="请输入登录邮箱" maxlength="24"/>
                    </div>
                    <div class="item">
                        <span class="icon icon-3"></span>
                        <input type="password" name="password" required placeholder="请输入密码" maxlength="20">
                        <span class="bind-password icon icon-4"></span>
                    </div>
                    <div class="item">
                        <span class="icon icon-3"></span>
                        <input type="password" name="password_confirmation" required placeholder="请再次输入密码" maxlength="20">
                        <span class="bind-password icon icon-4"></span>
                    </div>
                </div>
                <div class="tip">
                    <a href="{{ route('login') }}" class="forget-password">去登录</a>
                </div>
                @csrf
                <div class="layui-form-item" style="text-align:center; width:100%;height:100%;margin:0px;">
                    <button type="submit" class="login-btn">注册</button>
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
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePasswordVisibility = (bindPasswordElement) => {
            const passwordInput = bindPasswordElement.previousElementSibling;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        };

        const bindPasswordSpans = document.querySelectorAll('.bind-password');
        bindPasswordSpans.forEach((span) => {
            span.addEventListener('click', function () {
                togglePasswordVisibility(this);
            });
        });
    });
</script>
@include('layout.foot')
