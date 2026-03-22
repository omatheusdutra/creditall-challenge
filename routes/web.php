<?php

declare(strict_types=1);

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::redirect('/dashboard', '/');
