<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\SystemPromptController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ResourceFileController;

/*
|--------------------------------------------------------------------------
| Public (Siswa) - AMIRA Chat
|--------------------------------------------------------------------------
*/

Route::get('/', [ChatController::class, 'index'])
    ->name('amira.chat.index');

Route::post('/chat', [ChatController::class, 'chat'])
    ->name('amira.chat.send');

/*
|--------------------------------------------------------------------------
| Auth (Admin AMIRA)
|--------------------------------------------------------------------------
*/

// form login admin
Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->name('login');

// proses login
Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

// logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin AMIRA (hanya untuk user yang is_admin = true)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        })->name('home');

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::get('/system-prompt', [SystemPromptController::class, 'edit'])
            ->name('system-prompt.edit');
        Route::post('/system-prompt', [SystemPromptController::class, 'update'])
            ->name('system-prompt.update');

        Route::resource('subjects', SubjectController::class)->except(['show']);
        Route::resource('resources', ResourceFileController::class)->except(['show']);
    });
