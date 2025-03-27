<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $name = "jack";
    public $email = "1245689755@qq.com";
    public $password = "111111";
}
