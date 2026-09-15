<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Presentation\Api\Controller\EmployeeController;
use Presentation\Api\Controller\UserController;

/*
|--------------------------------------------------------------------------
| API ルート
|--------------------------------------------------------------------------
|
| プレゼンテーション層のコントローラのみを参照する。ユースケースの解決は
| App\Providers\DomainServiceProvider のバインディングに従う。
|
*/

Route::prefix('employees')->name('employees.')->group(function (): void {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/{id}', [EmployeeController::class, 'show'])->whereUuid('id')->name('show');
    Route::match(['put', 'patch'], '/{id}', [EmployeeController::class, 'update'])->whereUuid('id')->name('update');
    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->whereUuid('id')->name('destroy');
});

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{id}', [UserController::class, 'show'])->whereUuid('id')->name('show');
    Route::match(['put', 'patch'], '/{id}', [UserController::class, 'update'])->whereUuid('id')->name('update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->whereUuid('id')->name('destroy');
});
