<?php

use App\Http\Controllers\App\Artikel\ArtikelController;
use App\Http\Controllers\App\Banner\BannerController;
use App\Http\Controllers\App\Berita\BeritaController;
use App\Http\Controllers\App\CampusHiring\CampusHiringController;
use App\Http\Controllers\App\HakAkses\HakAksesController;
use App\Http\Controllers\App\Home\HomeController;
use App\Http\Controllers\App\Landingpage\LandingpageController;
use App\Http\Controllers\App\LowonganPekerjaan\LowonganPekerjaanController;
use App\Http\Controllers\App\Pengumuman\PengumumanController;
use App\Http\Controllers\App\Perusahaan\PerusahaanController;
use App\Http\Controllers\App\Todo\TodoController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:req-limit', 'handle.inertia'])->group(function () {

    Route::post('/apply-campus-hiring', [LandingpageController::class, 'storeLamaran'])
        ->name('landing.lamar-campus-hiring')
        ->middleware([\App\Http\Middleware\OptionalAuthMiddleware::class]);

    // === GROUP LANDING PAGE (Public & Optional Auth) ===
    Route::middleware([\App\Http\Middleware\OptionalAuthMiddleware::class])->group(function () {
        // Halaman Utama
        Route::get('/', [LandingpageController::class, 'index'])->name('landing.index');

        // Halaman Menu Khusus (URL Diubah agar tidak bentrok dengan Admin)
        Route::get('/daftar-perusahaan', [LandingpageController::class, 'perusahaan'])->name('landing.perusahaan');
        Route::get('/daftar-lowongan', [LandingpageController::class, 'lowongan'])->name('landing.lowongan');
        Route::get('/program-campus-hiring', [LandingpageController::class, 'campusHiring'])->name('landing.campus-hiring');
    });

    // SSO Routes
    Route::group(['prefix' => 'sso'], function () {
        Route::get('/callback', [AuthController::class, 'ssoCallback'])->name('sso.callback');
    });

    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::get('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/login-check', [AuthController::class, 'postLoginCheck'])->name('auth.login-check');
        Route::post('/login-post', [AuthController::class, 'postLogin'])->name('auth.login-post');
        Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/totp', [AuthController::class, 'totp'])->name('auth.totp');
        Route::post('/totp-post', [AuthController::class, 'postTotp'])->name('auth.totp-post');
    });

    // Protected Routes
    Route::group(['middleware' => 'check.auth'], function () {
        Route::get('/dashboard', [HomeController::class, 'index'])->name('home');

        Route::prefix('hak-akses')->group(function () {
            Route::get('/', [HakAksesController::class, 'index'])->name('hak-akses');
            Route::post('/change', [HakAksesController::class, 'postChange'])->name('hak-akses.change-post');
            Route::post('/delete', [HakAksesController::class, 'postDelete'])->name('hak-akses.delete-post');
            Route::post('/delete-selected', [HakAksesController::class, 'postDeleteSelected'])->name('hak-akses.delete-selected-post');
        });

        Route::prefix('todo')->group(function () {
            Route::get('/', [TodoController::class, 'index'])->name('todo');
            Route::post('/change', [TodoController::class, 'postChange'])->name('todo.change-post');
            Route::post('/delete', [TodoController::class, 'postDelete'])->name('todo.delete-post');
        });

        Route::prefix('perusahaan')->group(function () {
            Route::get('/', [PerusahaanController::class, 'index'])->name('perusahaan');
            Route::post('/change', [PerusahaanController::class, 'postChange'])->name('perusahaan.change-post');
            Route::post('/delete', [PerusahaanController::class, 'postDelete'])->name('perusahaan.delete-post');
        });
        // Pengumuman
        Route::prefix('pengumuman')->name('pengumuman.')->group(function () {
            Route::get('/', [PengumumanController::class, 'index'])->name('index');
            Route::post('/change', [PengumumanController::class, 'postChange'])->name('change');
            Route::post('/delete', [PengumumanController::class, 'postDelete'])->name('delete');
        });

        // Berita
        Route::prefix('berita')->name('berita.')->group(function () {
            Route::get('/', [BeritaController::class, 'index'])->name('index');
            Route::post('/change', [BeritaController::class, 'postChange'])->name('store');
            Route::post('/delete', [BeritaController::class, 'postDelete'])->name('delete');
        });

        // Artikel
        Route::prefix('artikel')->group(function () {
            Route::get('/', [ArtikelController::class, 'index'])->name('artikel');
            Route::post('/change', [ArtikelController::class, 'changePost'])->name('artikel.change-post');
            Route::post('/delete', [ArtikelController::class, 'deletePost'])->name('artikel.delete-post');
            Route::get('/detail/{id}', [ArtikelController::class, 'show'])->name('artikel.detail');
        });

        Route::prefix('lowongan-pekerjaan')->group(function () {
            Route::get('/', [LowonganPekerjaanController::class, 'index'])->name('lowongan-pekerjaan');
            Route::post('/change', [LowonganPekerjaanController::class, 'postChange'])->name('lowongan-pekerjaan.change-post');
            Route::post('/delete', [LowonganPekerjaanController::class, 'postDelete'])->name('lowongan-pekerjaan.delete-post');
        });

        Route::prefix('campus-hiring')->group(function () {
            Route::get('/', [CampusHiringController::class, 'index'])->name('campus-hiring');
            Route::post('/change', [CampusHiringController::class, 'postChange'])->name('campus-hiring.change-post');
            Route::post('/delete', [CampusHiringController::class, 'postDelete'])->name('campus-hiring.delete-post');
            Route::get('/download/{id}', [CampusHiringController::class, 'downloadApplicants'])->name('campus-hiring.download');
        });

        Route::prefix('banner')->group(function () {
            Route::get('/', [BannerController::class, 'index'])->name('banner');
            Route::post('/change', [BannerController::class, 'postChange'])->name('banner.change-post');
            Route::post('/delete', [BannerController::class, 'postDelete'])->name('banner.delete-post');
        });
    });
});
