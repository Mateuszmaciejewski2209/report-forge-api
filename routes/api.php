<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => 'ReportForge API',
    'version' => '1.0.0',
]));

Route::get('/dashboard', DashboardController::class);
Route::get('/reports', [ReportController::class, 'index']);
Route::post('/reports', [ReportController::class, 'store']);
Route::get('/reports/{code}', [ReportController::class, 'show']);
Route::post('/uploads', [UploadController::class, 'store']);
