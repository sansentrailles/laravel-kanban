<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use App\Http\Requests\Auth\RegisterUserRequest;

readonly class RegisterUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /**
     * Создаем DTO из валидированного FormRequest
     */
    public static function fromRequest(RegisterUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
