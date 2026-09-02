<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\RegisterUserDto;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    /**
     * Регистрируем нового пользователя
     */
    public function handle(RegisterUserDto $dto): User
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);

        event(new Registered($user));

        return $user;
    }
}
