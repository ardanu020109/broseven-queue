<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\QueueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\QueueItemController;
use App\Http\Controllers\Admin\QueueManagementController;
use App\Http\Controllers\Public\DisplayController;
use App\Http\Controllers\Admin\ColorSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebsiteSettingController;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/ambil-antrean', [QueueController::class, 'create'])->name('queue.create');

Route::post('/ambil-antrean', [QueueController::class, 'store'])
    ->middleware('throttle:queue-issue')
    ->name('queue.store');

// "signed": tanda tangan URL mencegah pengunjung mengganti ID tiket.
Route::get('/antrean/tiket/{queue}', [QueueController::class, 'ticket'])
    ->middleware('signed')
    ->name('queue.ticket');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/display', [DisplayController::class, 'index'])->name('display.index');

    Route::get('/display/data', [DisplayController::class, 'data'])
        ->middleware('throttle:display-data')
        ->name('display.data');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin (wajib login dan akun aktif)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

        // Master Antrean: Item Antrean
        Route::resource('queue-items', QueueItemController::class)->except('show');
        Route::patch('queue-items/{queue_item}/toggle', [QueueItemController::class, 'toggle'])->name('queue-items.toggle');
        Route::post('queue-items/{queue_item}/move', [QueueItemController::class, 'move'])->name('queue-items.move');

        // Pengelolaan antrean
        Route::get('queue-items/{queue_item}', [QueueManagementController::class, 'show'])->name('queue-items.show');
        Route::post('queue-items/{queue_item}/call-next', [QueueManagementController::class, 'callNext'])->name('queue-items.call-next');
        Route::post('queue-items/{queue_item}/recall', [QueueManagementController::class, 'recall'])->name('queue-items.recall');

        Route::post('queues/{queue}/call', [QueueManagementController::class, 'call'])->name('queues.call');
        Route::post('queues/{queue}/skip', [QueueManagementController::class, 'skip'])->name('queues.skip');
        Route::post('queues/{queue}/restore', [QueueManagementController::class, 'restore'])->name('queues.restore');
        Route::post('queues/{queue}/complete', [QueueManagementController::class, 'complete'])->name('queues.complete');

        // Pengaturan: user, website, warna
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

        Route::get('settings/website', [WebsiteSettingController::class, 'edit'])->name('settings.website.edit');
        Route::put('settings/website/{group}', [WebsiteSettingController::class, 'update'])
            ->where('group', 'general|contact|social|display|ticket')
            ->name('settings.website.update');

        Route::get('settings/colors', [ColorSettingController::class, 'edit'])->name('settings.colors.edit');
        Route::put('settings/colors', [ColorSettingController::class, 'update'])->name('settings.colors.update');
        Route::post('settings/colors/reset', [ColorSettingController::class, 'reset'])->name('settings.colors.reset');
    });
