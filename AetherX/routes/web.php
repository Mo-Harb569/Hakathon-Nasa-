<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanetController;
use App\Models\Planet;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    // جلب أول كوكب موجود (أو null إذا كانت القاعدة فارغة)
    $planet = Planet::first();

    // تحديد حالة التنقل المبدئية
    $isFirst = true;
    $isLast = true;
    
    // إذا كان هناك كوكب، نحدد حالة أزرار التنقل
    if ($planet) {
        // يمكنك تبسيط هذه الخطوات إذا كانت تسبب أخطاء
        $isFirst = (Planet::where('id', '<', $planet->id)->doesntExist());
        $isLast = (Planet::where('id', '>', $planet->id)->doesntExist());
    }

    // تمرير البيانات إلى الواجهة
    return view('welcome', compact('planet', 'isFirst', 'isLast'));
});