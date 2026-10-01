<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChangelogPdfController;
use App\Http\Controllers\FinalQuizController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebinarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AcademyController::class, 'home'])->name('academy.home');
Route::post('/name', [AcademyController::class, 'setName'])->name('academy.name');
Route::get('/courses', [AcademyController::class, 'courses'])->name('academy.courses');
Route::get('/tutorials', [AcademyController::class, 'tutorials'])->name('academy.tutorials');
Route::get('/tutorials/{tutorial:slug}', [AcademyController::class, 'tutorial'])->name('academy.tutorial');
Route::get('/webinars', [WebinarController::class, 'index'])->name('academy.webinars');
Route::get('/webinars/{webinar:slug}', [WebinarController::class, 'show'])->name('academy.webinar');
Route::get('/webinars/{webinar:slug}/join', [WebinarController::class, 'join'])->name('academy.webinar.join');
Route::get('/webinars/{webinar:slug}/recording', [WebinarController::class, 'recording'])->name('academy.webinar.recording');
Route::get('/search', [AcademyController::class, 'search'])->name('academy.search');
Route::get('/help', [AcademyController::class, 'help'])->name('academy.help');
Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('academy.case-studies.index');
Route::get('/case-studies/{caseStudy:slug}', [CaseStudyController::class, 'show'])->name('academy.case-studies.show');
Route::get('/sitemap.xml', [AcademyController::class, 'sitemap'])->name('sitemap');
Route::post('/locale', LocaleController::class)->name('locale.switch');

// Student authentication (public site)
Route::middleware('guest')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::get('/register', [StudentAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [StudentAuthController::class, 'register']);
    Route::get('/join', [StudentAuthController::class, 'showJoin'])->name('join');
    Route::post('/join', [StudentAuthController::class, 'join']);

    // Forgot password, for partners and staff alike — the panel's login links
    // here too. Throttled so nobody can walk a list of addresses through it;
    // the broker adds one link per minute per person on top.
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});
// Personal passwordless access link (magic link)
Route::get('/enter/{token}', [StudentAuthController::class, 'enter'])->name('academy.enter');
Route::post('/logout', [StudentAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/courses/{course:slug}', [AcademyController::class, 'course'])->name('academy.course');
Route::get('/courses/{course:slug}/lessons/{lesson:slug}', [AcademyController::class, 'lesson'])->name('academy.lesson');
Route::post('/courses/{course:slug}/lessons/{lesson:slug}/quiz/start', [AcademyController::class, 'startQuiz'])->name('academy.quiz.start');
Route::post('/courses/{course:slug}/lessons/{lesson:slug}/quiz', [AcademyController::class, 'submitQuiz'])->name('academy.quiz');

// Remembering a place in a video, and what a student thought of a course, both
// belong to an account — there is nowhere to keep them for an anonymous visitor.
Route::middleware('auth')->group(function () {
    Route::post('/courses/{course:slug}/lessons/{lesson:slug}/position', [AcademyController::class, 'saveVideoPosition'])
        ->name('academy.lesson.position');
    Route::post('/courses/{course:slug}/feedback', [AcademyController::class, 'saveFeedback'])
        ->name('academy.course.feedback');
});

// Final quiz + student certificates (logged-in students only)
Route::middleware('auth')->group(function () {
    Route::get('/courses/{course:slug}/final-quiz', [FinalQuizController::class, 'show'])->name('academy.final.show');
    Route::post('/courses/{course:slug}/final-quiz/start', [FinalQuizController::class, 'start'])->name('academy.final.start');
    Route::post('/courses/{course:slug}/final-quiz', [FinalQuizController::class, 'submit'])->name('academy.final.submit');

    Route::get('/my/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/my/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    Route::get('/my/profile', [ProfileController::class, 'edit'])->name('academy.profile');
    Route::put('/my/profile', [ProfileController::class, 'update'])->name('academy.profile.update');
    Route::put('/my/profile/password', [ProfileController::class, 'updatePassword'])->name('academy.profile.password');
});

// What's new as a PDF, one release or all of them. Panel users only — the
// controller checks canAccessPanel(), the same rule as the page itself.
Route::get('/admin/changelog/pdf/{release?}', ChangelogPdfController::class)
    ->middleware('auth')
    ->name('changelog.pdf');

// Public certificate verification
Route::get('/certificates/{number}', [CertificateController::class, 'verify'])->name('certificates.verify');
