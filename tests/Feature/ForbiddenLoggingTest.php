<?php

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('journalise chaque acces interdit sur le canal business', function () {
    $user = User::factory()->manager()->create();

    $businessLogger = Mockery::spy(LoggerInterface::class);

    Log::spy();
    Log::shouldReceive('channel')->with('business')->andReturn($businessLogger);

    $request = Request::create('/api/v1/expenses', 'GET');
    $request->setUserResolver(fn () => $user);

    app(ExceptionHandler::class)->render(
        $request,
        new HttpException(403, 'Acces interdit')
    );

    $businessLogger->shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => $message === 'Acces interdit'
            && $context['user_id'] === $user->id
            && $context['farm_id'] === $user->farm_id
            && $context['method'] === 'GET'
    );
});
