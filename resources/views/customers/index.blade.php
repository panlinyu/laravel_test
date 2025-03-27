@include('layout.head')
<div class="layui-layout layui-layout-admin">
    <div class="layui-header">
        <div class="layui-logo">客户管理系统</div>
        <ul class="layui-nav layui-layout-right">
            <li class="layui-nav-item">
                <img src="/static/admin/images/fff.png" alt="头像" class="user-avatar">
            </li>
            <li class="layui-nav-item">
                <a href="{{ route('logout') }}">退出登录</a>
            </li>
        </ul>
    </div>
    <div class="layui-side layui-bg-black">
        <div class="layui-side-scroll">
            <ul class="layui-nav layui-nav-tree" lay-filter="leftMenu">
                <li class="layui-nav-item layui-nav-itemed layui-this">
                    <a href="{{ route('customers.index') }}">客户管理</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="layui-body">
        <div class="layui-form">
            <div class="layui-form-item">
                <div class="layui-inline">
                    <input type="text" name="keyword" placeholder="请输入姓名关键词" class="layui-input">
                </div>
                <div class="layui-inline">
                    <button class="layui-btn" lay-submit lay-filter="search">搜索</button>
                </div>
                <div class="layui-inline">
                    <button class="layui-btn layui-btn-normal" id="addCustomer">增加客户</button>
                </div>
            </div>
        </div>
        <table id="customerTable" lay-filter="customerTable"></table>
    </div>
</div>

<script>
    layui.use(['table', 'form', 'layer', 'jquery'], function () {
        var table = layui.table;
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.$;

        // 获取 CSRF Token
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // 从 localStorage 中获取 token 信息
        var storedTokens = localStorage.getItem('tokens');
        var tokens = storedTokens ? JSON.parse(storedTokens) : null;

        // 检查 token 是否存在且未过期
        function isTokenValid() {
            if (tokens) {
                var expiresAt = new Date(tokens.expires_at);
                var now = new Date();
                var fiveMinutesFromNow = new Date(now.getTime() + 5 * 60 * 1000);
                return expiresAt > fiveMinutesFromNow;
            }
            return false;
        }

        // 使用 refresh_token 获取新的 token
        function refreshToken() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '/oauth/token',
                    type: 'POST',
                    data: {
                        grant_type: 'refresh_token',
                        client_id: "{{$client_id}}",
                        client_secret: "{{$client_secret}}",
                        refresh_token: tokens.refresh_token,
                        scope: ''
                    },
                    timeout: 3000, // 设置超时时间
                    success: function (response) {
                        var newTokens = {
                            access_token: response.access_token,
                            refresh_token: response.refresh_token,
                            expires_at: new Date(new Date().getTime() + response.expires_in * 1000)
                        };
                        localStorage.setItem('tokens', JSON.stringify(newTokens));
                        tokens = newTokens;
                        resolve(newTokens.access_token);
                    },
                    error: function (error) {
                        layer.msg('刷新 token 失败', { icon: 2 });
                        reject(error);
                    }
                });
            });
        }

        // 获取有效的 token
        async function getValidToken() {
            if (!isTokenValid()) {
                if (tokens && tokens.refresh_token) {
                    try {
                        return await refreshToken();
                    } catch (error) {
                        // 如果刷新失败，重新获取 token
                        return await getNewToken();
                    }
                } else {
                    return await getNewToken();
                }
            }
            return tokens.access_token;
        }

        // 使用 Passport 密码授权模式获取新的 token
        function getNewToken() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '/oauth/token',
                    type: 'POST',
                    data: {
                        grant_type: 'password',
                        client_id: "{{$client_id}}",
                        client_secret: "{{$client_secret}}",
                        username: "{{ Auth::user()->email }}",
                        password: "{{session('pwd')}}",
                        scope: ''
                    },
                    timeout: 3000, // 设置超时时间
                    success: function (response) {
                        var newTokens = {
                            access_token: response.access_token,
                            refresh_token: response.refresh_token,
                            expires_at: new Date(new Date().getTime() + response.expires_in * 1000)
                        };
                        localStorage.setItem('tokens', JSON.stringify(newTokens));
                        tokens = newTokens;
                        resolve(newTokens.access_token);
                    },
                    error: function (error) {
                        layer.msg('获取 token 失败', { icon: 2 });
                        reject(error);
                    }
                });
            });
        }

        // 处理 401 handle401Error错误 重新获取token
        function handle401Error() {
            localStorage.removeItem('tokens');
            tokens = null;
            getValidToken().then(() => {
                // 重新渲染表格
                renderTable();
            });
        }

        // 渲染表格
        async function renderTable() {
            var token = await getValidToken();
            table.render({
                elem: '#customerTable',
                url: "/customers",
                cols: [[
                    { field: 'id', title: 'ID', width: 80 },
                    { field: 'last_name', title: '姓名', width: 100 },
                    { field: 'first_name', title: '姓氏', width: 100 },
                    { field: 'age', title: '年龄', width: 80 },
                    { field: 'dob', title: '出生日期', width: 150 },
                    { field: 'email', title: '邮箱', width: 200 },
                    { title: '操作', width: 250, toolbar: '#barDemo' }
                ]],
                page: true,
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                done: function (res, curr, count) {
                    if (res.data.length === 0) {
                        var tableEl = $(this.elem).next('.layui-table-view');
                        tableEl.find('.layui-table-body').html('<div style="text-align: center; padding: 20px;">无数据</div>');
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 401) {
                        handle401Error();
                    }
                }
            });
        }

        // 搜索按钮监听
        form.on('submit(search)', async function (data) {
            var token = await getValidToken();
            table.reload('customerTable', {
                where: {
                    ...data.field,
                    limit: this.limit,
                    '_token': csrfToken
                },
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                error: function (xhr) {
                    if (xhr.status === 401) {
                        handle401Error();
                    }
                }
            });
            return false;
        });

        // 增加客户按钮监听
        document.getElementById('addCustomer').addEventListener('click', function () {
            layer.open({
                type: 1,
                title: '新增客户',
                content: `
                    <form class="layui-form" id="addCustomerForm">
                        <div class="layui-form-item">
                            <label class="layui-form-label">名字</label>
                            <div class="layui-input-block">
                                <input type="text" name="first_name" required  lay-verify="required" placeholder="请输入名字" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">姓氏</label>
                            <div class="layui-input-block">
                                <input type="text" name="last_name" required  lay-verify="required" placeholder="请输入姓氏" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">年龄</label>
                            <div class="layui-input-block">
                                <input type="number" name="age" required  lay-verify="required" placeholder="请输入年龄" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">出生日期</label>
                            <div class="layui-input-block">
                                <input type="date" name="dob" required  lay-verify="required" placeholder="请输入出生日期" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">邮箱</label>
                            <div class="layui-input-block">
                                <input type="email" name="email" required  lay-verify="required|email" placeholder="请输入邮箱" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button class="layui-btn" lay-submit lay-filter="addCustomerSubmit">提交</button>
                            </div>
                        </div>
                    </form>
                `,
                area: ['400px', '500px'],
                offset: 'auto',
                success: function () {
                    form.render();
                }
            });
        });

        // 新增客户
        form.on('submit(addCustomerSubmit)', async function (data) {
            var token = await getValidToken();
            $.ajax({
                url: '/customers',
                type: 'POST',
                data: data.field,
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function (res) {
                    if (res.code === 0) {
                        layer.closeAll();
                        table.reload('customerTable');
                        layer.msg('新增客户成功');
                    } else {
                        layer.msg('新增客户失败', { icon: 2 });
                    }
                },
                error: function (err) {
                    if (err.status === 401) {
                        handle401Error();
                    } else if (err.status === 422) {
                        var errors = err.responseJSON.errors;
                        var errorMsg = '';
                        for (var key in errors) {
                            errorMsg += errors[key][0] + '<br>';
                        }
                        layer.msg(errorMsg, { icon: 2 });
                    } else {
                        layer.msg('新增客户失败', { icon: 2 });
                    }
                }
            });
            return false;
        });

        // 表格操作列监听
        table.on('tool(customerTable)', async function (obj) {
            var data = obj.data;
            var token = await getValidToken();
            if (obj.event === 'del') {
                layer.confirm('确定要删除该客户吗？', function (index) {
                    $.ajax({
                        url: `/customers/${data.id}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Authorization': 'Bearer ' + token
                        },
                        success: function (res) {
                            obj.del();
                            layer.close(index);
                            layer.msg('删除客户成功');
                        },
                        error: function (err) {
                            if (err.status === 401) {
                                handle401Error();
                            } else {
                                layer.close(index);
                                layer.msg('删除客户失败', { icon: 2 });
                            }
                        }
                    });
                });
            } else if (obj.event === 'edit') {
                layer.open({
                    type: 1,
                    title: '编辑客户信息',
                    content: `
                        <form class="layui-form" id="editCustomerForm">
                            <div class="layui-form-item">
                                <label class="layui-form-label">名字</label>
                                <div class="layui-input-block">
                                    <input type="text" name="first_name" value="${data.first_name}" placeholder="请输入名字" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">姓氏</label>
                                <div class="layui-input-block">
                                    <input type="text" name="last_name" value="${data.last_name}" placeholder="请输入姓氏" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">年龄</label>
                                <div class="layui-input-block">
                                    <input type="number" name="age" value="${data.age}" placeholder="请输入年龄" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">出生日期</label>
                                <div class="layui-input-block">
                                    <input type="date" name="dob" value="${data.dob}" placeholder="请输入出生日期" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">邮箱</label>
                                <div class="layui-input-block">
                                    <input type="email" name="email" value="${data.email}" placeholder="请输入邮箱" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <input type="hidden" name="id" value="${data.id}">
                                    <button class="layui-btn" lay-submit lay-filter="editCustomerSubmit">提交</button>
                                </div>
                            </div>
                        </form>
                    `,
                    area: ['400px', '500px'],
                    offset: 'auto',
                    success: function () {
                        form.render();
                    }
                });
            }
        });

        // 编辑客户
        form.on('submit(editCustomerSubmit)', async function (data) {
            var id = data.field.id;
            delete data.field.id;
            var token = await getValidToken();
            $.ajax({
                url: `/customers/${id}`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Authorization': 'Bearer ' + token
                },
                data: data.field,
                success: function (res) {
                    if (res.code === 0) {
                        layer.closeAll();
                        table.reload('customerTable');
                        layer.msg('编辑客户成功');
                    } else {
                        layer.msg('编辑客户失败', { icon: 2 });
                    }
                },
                error: function (err) {
                    if (err.status === 401) {
                        handle401Error();
                    } else if (err.status === 422) {
                        var errors = err.responseJSON.errors;
                        var errorMsg = '';
                        for (var key in errors) {
                            errorMsg += errors[key][0] + '<br>';
                        }
                        layer.msg(errorMsg, { icon: 2 });
                    } else {
                        layer.msg('编辑客户失败', { icon: 2 });
                    }
                }
            });
            return false;
        });

        // 初始化表格
        renderTable();
    });
</script>
<!-- 表格操作列模板 -->
<script type="text/html" id="barDemo">
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="edit">编辑</a>
</script>
@include('layout.foot')
