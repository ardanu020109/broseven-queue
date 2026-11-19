<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdminIsActive;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdminIsActive::class,
        ]);

        $middleware->appendToGroup('web', SecurityHeaders::class);

        $middleware->redirectGuestsTo(fn(Request $request) => route('login'));
        $middleware->redirectUsersTo(fn(Request $request) => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->routeIs('queue.store')) {
                $seconds = $e->getHeaders()['Retry-After'] ?? 60;

                return back()->withInput()->withErrors([
                    'queue_item_id' => "Terlalu banyak permintaan. Coba lagi dalam {$seconds} detik.",
                ]);
            }
        });
    })->create();
