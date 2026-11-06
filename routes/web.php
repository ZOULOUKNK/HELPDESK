<?php

use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\User\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(UserPreferenceController::homeRouteFor(auth()->user())));

Route::get('/deconnexion', fn () => view('auth.logout'))->name('logout.page');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // 5 tentatives par minute et par adresse IP : freine le devinage de mots de passe.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// auth.session : un changement de mot de passe ferme les autres sessions.
Route::middleware(['auth', 'auth.session'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');

    // Mon compte : profil, mot de passe, préférences (tous les rôles)
    Route::prefix('mon-compte')->name('preferences.')->group(function () {
        Route::get('/', [UserPreferenceController::class, 'edit'])->name('edit');
        Route::put('/', [UserPreferenceController::class, 'update'])->name('update');
        Route::put('/profil', [UserPreferenceController::class, 'updateProfile'])->name('profile');
        Route::put('/mot-de-passe', [UserPreferenceController::class, 'updatePassword'])
            ->middleware('throttle:5,1')
            ->name('password');
    });

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