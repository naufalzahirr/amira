<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\Teacher\ClassroomController;
use App\Http\Controllers\Teacher\KeyController;
use App\Http\Controllers\Teacher\MentorController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->middleware('guest')->name('home');

Route::view('/privasi', 'privacy')->name('privasi');
Route::get('/gabung/{code}', [JoinController::class, 'show'])->middleware('throttle:30,1')->name('gabung');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/lupa-sandi', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', [PasswordController::class, 'edit'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [PasswordController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::get('/beranda', fn () => redirect()->route(auth()->user()->isTeacher() ? 'guru.index' : 'belajar.index'))
        ->name('dashboard');

    // Murid (guru juga boleh mencoba mentor di kelasnya sendiri)
    Route::get('/belajar', [LearnController::class, 'index'])->name('belajar.index');
    Route::post('/belajar/gabung', [LearnController::class, 'join'])->middleware('throttle:20,1')->name('belajar.join');
    Route::get('/belajar/kelas/{classroom}', [LearnController::class, 'classroom'])->name('belajar.kelas');

    Route::get('/mentor/{mentor}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/mentor/{mentor}/percakapan/{conversation}', [ChatController::class, 'show'])->name('chat.conversation');
    Route::post('/mentor/{mentor}/kirim', [ChatController::class, 'send'])->middleware('throttle:30,1')->name('chat.send');
    Route::get('/lampiran/{message}', [ChatController::class, 'image'])->name('chat.image');
    Route::delete('/percakapan/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');

    Route::post('/privasi/setuju', [AccountController::class, 'ackPrivacy'])->name('privasi.setuju');
    Route::get('/akun', [AccountController::class, 'edit'])->name('akun.edit');
    Route::put('/akun', [AccountController::class, 'update'])->name('akun.update');
    Route::put('/akun/sandi', [AccountController::class, 'password'])->name('akun.password');
    Route::post('/akun/tes-key', [AccountController::class, 'testKey'])->middleware('throttle:10,1')->name('akun.test');

    // Guru
    Route::middleware('guru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('/', [ClassroomController::class, 'index'])->name('index');
        Route::post('/kelas', [ClassroomController::class, 'store'])->name('kelas.store');
        Route::get('/kelas/{classroom}', [ClassroomController::class, 'show'])->name('kelas.show');
        Route::put('/kelas/{classroom}', [ClassroomController::class, 'update'])->name('kelas.update');
        Route::post('/kelas/{classroom}/radar/usulan', [ClassroomController::class, 'suggest'])->middleware('throttle:6,1')->name('kelas.suggest');
        Route::get('/kelas/{classroom}/qr', [ClassroomController::class, 'qr'])->name('kelas.qr');
        Route::post('/kelas/{classroom}/kode-baru', [ClassroomController::class, 'regenerateCode'])->name('kelas.code');
        Route::delete('/kelas/{classroom}/murid/{user}', [ClassroomController::class, 'removeStudent'])->name('kelas.murid.remove');

        Route::get('/kelas/{classroom}/mentor/baru', [MentorController::class, 'create'])->name('mentor.create');
        Route::post('/kelas/{classroom}/mentor', [MentorController::class, 'store'])->name('mentor.store');
        Route::get('/mentor/{mentor}/ubah', [MentorController::class, 'edit'])->name('mentor.edit');
        Route::put('/mentor/{mentor}', [MentorController::class, 'update'])->name('mentor.update');
        Route::delete('/mentor/{mentor}', [MentorController::class, 'destroy'])->name('mentor.destroy');
        Route::post('/mentor/{mentor}/materi', [MentorController::class, 'storeMaterial'])->name('mentor.materi.store');
        Route::delete('/materi/{material}', [MentorController::class, 'destroyMaterial'])->name('mentor.materi.destroy');

        Route::get('/pengaturan-ai', [KeyController::class, 'edit'])->name('keys.edit');
        Route::put('/pengaturan-ai', [KeyController::class, 'update'])->name('keys.update');
        Route::post('/pengaturan-ai/tes', [KeyController::class, 'test'])->middleware('throttle:10,1')->name('keys.test');
    });
});
