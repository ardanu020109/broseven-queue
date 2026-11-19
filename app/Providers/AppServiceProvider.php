<?php

namespace App\Providers;

use App\Models\Queue;
use App\Observers\QueueObserver;
use App\View\Composers\AdminComposer;
use App\View\Composers\SiteComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(
            ['layouts.*', 'auth.*', 'public.*', 'display.*', 'tickets.*', 'admin.*'],
            SiteComposer::class
        );

        View::composer('layouts.admin', AdminComposer::class);

        // Menyiarkan setiap perubahan antrean ke layar display.
        Queue::observe(QueueObserver::class);

        // Pengambilan nomor antrean oleh pengunjung.
        RateLimiter::for('queue-issue', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinutes(60, 200)->by($request->ip()),
        ]);

        // Data untuk layar display (polling cadangan dan penyegaran).
        RateLimiter::for('display-data', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
