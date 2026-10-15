<?php

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('journalise chaque acces interdit', function () {
    $user = User::factory()->manager()->create();

    Log::spy();

    $request = Request::create('/api/v1/expenses', 'GET');
    $request->setUserResolver(fn () => $user);

    app(ExceptionHandler::class)->render(
        $request,
        new HttpException(403, 'Acces interdit')
    );

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => $message === 'Acces interdit'
            && $context['user_id'] === $user->id
            && $context['farm_id'] === $user->farm_id
            && $context['method'] === 'GET'
    );
});
