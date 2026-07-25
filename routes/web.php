<?php

use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/deconnexion', fn () => view('auth.logout'))->name('logout.page');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Tickets
    |--------------------------------------------------------------------------
    */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');

        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('status');
        Route::post('/{ticket}/assign', [TicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('priority');
        Route::post('/{ticket}/rate', [TicketController::class, 'rate'])->name('rate');
    });

    /*
    |--------------------------------------------------------------------------
    | Administration générale
    |--------------------------------------------------------------------------
    | Accessible à admin + super_admin.
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        Route::get('/', fn () => redirect()->route('admin.users.index'))->name('home');

        /*
        |--------------------------------------------------------------------------
        | Gestion utilisateurs
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');

            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

            Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

        /*
        |--------------------------------------------------------------------------
        | Zone super administrateur uniquement
        |--------------------------------------------------------------------------
        | La vue direction et la configuration globale ne doivent être visibles
        | que par le profil super_admin.
        */
        Route::middleware('super_admin')->group(function () {
            Route::get('/stats', [StatsController::class, 'index'])->name('stats');

            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [AppSettingsController::class, 'edit'])->name('edit');
                Route::put('/', [AppSettingsController::class, 'update'])->name('update');
            });
        });
    });
});