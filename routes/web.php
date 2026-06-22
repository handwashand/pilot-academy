<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\Auth\StudentAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AcademyController::class, 'home'])->name('academy.home');
Route::post('/name', [AcademyController::class, 'setName'])->name('academy.name');

// Student authentication (public site)
Route::middleware('guest')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::get('/register', [StudentAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [StudentAuthController::class, 'register']);
});
Route::post('/logout', [StudentAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/courses/{course:slug}', [AcademyController::class, 'course'])->name('academy.course');
Route::get('/courses/{course:slug}/lessons/{lesson:slug}', [AcademyController::class, 'lesson'])->name('academy.lesson');
Route::post('/courses/{course:slug}/lessons/{lesson:slug}/quiz', [AcademyController::class, 'submitQuiz'])->name('academy.quiz');
