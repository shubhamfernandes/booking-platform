<?php

use App\Domain\Bookings\Exceptions\BookingOverlapException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (BookingOverlapException $e, $request) {
            return new JsonResponse([
                'errors' => [
                    'user_id' => [
                        'This booking overlaps another booking for the selected user.',
                    ],
                ],
            ], 422);
        });
    })->create();
