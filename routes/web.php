<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PomodoroController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard / student overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Subjects (المواد الدراسية)
    Route::resource('subjects', SubjectController::class);

    // Assignments & exams (الواجبات والاختبارات)
    Route::resource('assignments', AssignmentController::class)
        ->parameters(['assignments' => 'assignment']);
    Route::patch('assignments/{assignment}/complete', [AssignmentController::class, 'complete'])
        ->name('assignments.complete');

    // Study schedule (جدولة جلسات الدراسة)
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('schedule/create', [ScheduleController::class, 'create'])->name('schedule.create');
    Route::post('schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::get('schedule/{session}/edit', [ScheduleController::class, 'edit'])->name('schedule.edit');
    Route::put('schedule/{session}', [ScheduleController::class, 'update'])->name('schedule.update');
    Route::delete('schedule/{session}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

    // Smart scheduling (الجدولة الذكية)
    Route::post('schedule/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::post('schedule/resolve-conflicts', [ScheduleController::class, 'resolveConflicts'])->name('schedule.resolve-conflicts');
    Route::post('schedule/{session}/reschedule', [ScheduleController::class, 'reschedule'])->name('schedule.reschedule');
    Route::post('schedule/availability', [ScheduleController::class, 'storeAvailability'])->name('schedule.availability');

    // Pomodoro focus sessions (وقت التركيز)
    Route::get('pomodoro', [PomodoroController::class, 'index'])->name('pomodoro.index');
    Route::post('pomodoro', [PomodoroController::class, 'store'])->name('pomodoro.store');
    Route::delete('pomodoro/{pomodoro}', [PomodoroController::class, 'destroy'])->name('pomodoro.destroy');

    // Daily energy tracking (تتبع الطاقة)
    Route::get('energy', [EnergyLogController::class, 'index'])->name('energy.index');
    Route::post('energy', [EnergyLogController::class, 'store'])->name('energy.store');
    Route::delete('energy/{log}', [EnergyLogController::class, 'destroy'])->name('energy.destroy');

    // Notifications (الإشعارات)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
