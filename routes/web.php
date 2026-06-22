<?php

use App\Http\Controllers\AcademyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AcademyController::class, 'home'])->name('academy.home');
Route::post('/name', [AcademyController::class, 'setName'])->name('academy.name');

Route::get('/courses/{course:slug}', [AcademyController::class, 'course'])->name('academy.course');
Route::get('/courses/{course:slug}/lessons/{lesson:slug}', [AcademyController::class, 'lesson'])->name('academy.lesson');
Route::post('/courses/{course:slug}/lessons/{lesson:slug}/quiz', [AcademyController::class, 'submitQuiz'])->name('academy.quiz');
