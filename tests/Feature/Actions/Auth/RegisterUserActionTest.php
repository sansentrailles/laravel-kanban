<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUserAction;
use App\DTO\Auth\RegisterUserDto;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('registers a new user and dispatches event', function () {
    Event::fake();

    $dto = new RegisterUserDto(
        name: 'Ivan Ivanov',
        email: 'ivanov@gmail.com',
        password: '123123',
    );

    $action = app(RegisterUserAction::class);
    $user = $action->handle($dto);

    expect($user)
        ->toBeInstanceOf(User::class)
        ->name->toBe('Ivan Ivanov')
        ->email->toBe('ivanov@gmail.com')
        ->password->not->toBe('123123');

    Event::assertDispatched(Registered::class, function ($event) use ($user) {
        return $event->user->is($user);
    });

    expect(User::query()->where('email', 'ivanov@gmail.com')->exists())->toBeTrue();
});
