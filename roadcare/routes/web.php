<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? '/dashboard' : '/login'));
Route::post('/language', function (Request $request) {
    $data = $request->validate(['locale' => ['required', 'in:en,ckb']]);
    $request->session()->put('locale', $data['locale']);
    return back();
})->name('language');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/photo', [ReportController::class, 'photo'])->name('reports.photo');
    Route::get('/map', [ReportController::class, 'map'])->name('map');
    Route::middleware('government')->group(function () {
        Route::get('/government', [ReportController::class, 'dashboard'])->name('government.dashboard');
        Route::get('/government/reports', [ReportController::class, 'index'])->name('government.reports');
        Route::patch('/government/reports/{report}', [ReportController::class, 'update'])->name('government.update');
    });
});
