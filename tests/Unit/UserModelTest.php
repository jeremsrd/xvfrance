<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_password_and_token_are_never_serialized(): void
    {
        $user = new User(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret']);
        $user->remember_token = 'token';

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $this->assertSame('Admin', $user->name);
    }
}
