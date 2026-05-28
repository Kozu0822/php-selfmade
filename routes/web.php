<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('top');

Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::get('/register/confirm', function () {
    return redirect()->route('register');
});
Route::post('/register/confirm', [RegisterController::class, 'confirm'])->name('register.confirm');
Route::get('/register/complete', function () {
    return redirect()->route('register');
});
Route::post('/register/complete', [RegisterController::class, 'complete'])->name('register.complete');

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('customer')->group(function () {
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations/device', [ReservationController::class, 'storeDevice'])->name('reservations.device.store');
    Route::get('/reservations/symptom', [ReservationController::class, 'symptom'])->name('reservations.symptom');
    Route::post('/reservations/symptom', [ReservationController::class, 'storeSymptom'])->name('reservations.symptom.store');
    Route::post('/reservations/symptom/ai', [ReservationController::class, 'suggestSymptom'])->name('reservations.symptom.ai');
    Route::get('/reservations/time', [ReservationController::class, 'time'])->name('reservations.time');
    Route::post('/reservations/time', [ReservationController::class, 'storeTime'])->name('reservations.time.store');
    Route::get('/reservations/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/complete', [ReservationController::class, 'complete'])->name('reservations.complete');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
});

Route::middleware('admin')->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/reservations/{reservation}/cancel', [DashboardController::class, 'cancel'])->name('admin.reservations.cancel');
    Route::post('/admin/time-slots', [DashboardController::class, 'storeTimeSlot'])->name('admin.time_slots.store');
    Route::post('/admin/time-slots/{timeSlot}/toggle', [DashboardController::class, 'toggleTimeSlot'])->name('admin.time_slots.toggle');
    Route::post('/admin/time-slots/{timeSlot}/delete', [DashboardController::class, 'destroyTimeSlot'])->name('admin.time_slots.destroy');
    Route::post('/admin/devices', [DashboardController::class, 'storeDevice'])->name('admin.devices.store');
    Route::post('/admin/devices/{device}/update', [DashboardController::class, 'updateDevice'])->name('admin.devices.update');
    Route::post('/admin/devices/{device}/delete', [DashboardController::class, 'destroyDevice'])->name('admin.devices.destroy');
    Route::post('/admin/devices/{device}/restore', [DashboardController::class, 'restoreDevice'])->name('admin.devices.restore');
    Route::post('/admin/symptoms', [DashboardController::class, 'storeSymptom'])->name('admin.symptoms.store');
    Route::post('/admin/symptoms/{symptom}/update', [DashboardController::class, 'updateSymptom'])->name('admin.symptoms.update');
    Route::post('/admin/symptoms/{symptom}/delete', [DashboardController::class, 'destroySymptom'])->name('admin.symptoms.destroy');
    Route::post('/admin/symptoms/{symptom}/restore', [DashboardController::class, 'restoreSymptom'])->name('admin.symptoms.restore');
    Route::post('/admin/parts', [DashboardController::class, 'storePart'])->name('admin.parts.store');
    Route::post('/admin/parts/{part}/update', [DashboardController::class, 'updatePart'])->name('admin.parts.update');
    Route::post('/admin/parts/{part}/delete', [DashboardController::class, 'destroyPart'])->name('admin.parts.destroy');
    Route::post('/admin/parts/{part}/restore', [DashboardController::class, 'restorePart'])->name('admin.parts.restore');
});
