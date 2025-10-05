<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanetController;

// مسار افتراضي للمصادقة (يمكن تركه أو حذفه إذا كنت لا تستخدم المصادقة)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🌟 مسار التنقل (يستجيب لـ /api/navigate-target/...) 🌟
Route::get('/navigate-target/{id}/{direction}', [PlanetController::class, 'navigateTarget']);

// 🌟 مسار التحليل الفوري (يستجيب لـ /api/analyze-new) 🌟
Route::post('/analyze-new', [PlanetController::class, 'analyze']);

Route::get('/get-planet/{id}', [PlanetController::class, 'show']);