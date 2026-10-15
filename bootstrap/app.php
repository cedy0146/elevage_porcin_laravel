<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Journalisation de chaque acces interdit (403).
        $exceptions->render(function (Throwable $e, Request $request): void {
            if ($e instanceof HttpException && $e->getStatusCode() === 403) {
                Log::channel('business')->warning('Acces interdit', [
                    'user_id' => $request->user()?->id,
                    'farm_id' => $request->user()?->farm_id,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
        });
    })->create();
