<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Models\Lesson;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $tracks = Track::with('lessons', 'badge')->get();
    $user = Auth::user() ?? User::first(); // Pega o primeiro se não logado

    // Ranking Logic
    $rankingPosition = User::where('xp', '>', ($user->xp ?? 0))->count() + 1;

    // Next Lesson Logic
    $completedLessonIds = $user ? $user->progress()->where('status', 'completed')->pluck('lesson_id')->toArray() : [];
    $nextLesson = Lesson::with('track')->whereNotIn('id', $completedLessonIds)
        ->orderBy('track_id')
        ->orderBy('order')
        ->first();

    return view('welcome', compact('tracks', 'user', 'rankingPosition', 'nextLesson'));
});

Route::post('/progress/update-xp', [ProgressController::class, 'updateXP']);
Route::post('/progress/complete-lesson/{id}', [ProgressController::class, 'completeLesson']);
Route::post('/progress/freeze-streak', [ProgressController::class, 'freezeStreak']);
Route::get('/progress/ranking-json', [ProgressController::class, 'ranking']);

Route::get('/ranking', function () {
    $users = User::orderBy('xp', 'desc')->take(20)->get();

    return view('ranking', compact('users'));
})->name('ranking');

Route::get('/desafios', function () {
    return view('desafios');
})->name('desafios');

Route::get('/dashboard', function () {
    return redirect('/'); // Redireciona para a home para manter a fluidez
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
