<?php

namespace App\Http\Controllers;

use App\Models\Planet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http; // ✅ فقط هنا في الأعلى

class PlanetController extends Controller
{
    // -------------------------------
    // تنقل بين الكواكب (التالي/السابق)
    // -------------------------------
    public function navigateTarget($id, $direction)
    {
        if ($id == 0 || $direction === 'current') {
            $planet = Planet::latest('id')->first(); 
        } elseif ($direction === 'next') {
            $planet = Planet::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction === 'prev') {
            $planet = Planet::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } else {
            $planet = Planet::find($id);
        }

        if (!$planet) {
            return response()->json([], 404);
        }

        $planetData = $planet->attributesToArray();
        $formattedData = [];
        foreach ($planetData as $key => $value) {
            $formattedData[Str::camel($key)] = $value;
        }

        $minId = Planet::min('id');
        $maxId = Planet::max('id');
        $formattedData['isFirst'] = $planet->id == $minId;
        $formattedData['isLast'] = $planet->id == $maxId;

        return response()->json($formattedData);
    }

    // -------------------------------
    // تحليل كوكب جديد وإرسال البيانات للـ ML
    // -------------------------------
 public function analyze(Request $request)
    {
        // 1. التحقق من المدخلات
        $validatedData = $request->validate([
            'planet_mass' => 'required|numeric',
            'planet_radius' => 'required|numeric',
            'orbital_period' => 'required|numeric',
            'distance' => 'required|numeric',
            'surface_temperature' => 'required|numeric',
            'atmosphere_pressure' => 'required|numeric',
            'orbital_eccentricity' => 'required|numeric',
        ]);

        // 2. إرسال البيانات إلى ML API
        try {
            $mlResponse = Http::post('http://127.0.0.1:5000/predict', $validatedData);
            if (!$mlResponse->successful()) {
                return response()->json(['error' => 'ML API failed'], 500);
            }
            $mlResult = $mlResponse->json(); // { ml_disposition, habitability_score, planet_type }
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء المعالجة', 'message' => $e->getMessage()], 500);
        }

        // 3. حفظ البيانات في قاعدة البيانات
        $newPlanet = Planet::create([
            'input_data' => json_encode($validatedData),
            'planet_mass' => $validatedData['planet_mass'],
            'planet_radius' => $validatedData['planet_radius'],
            'orbital_period' => $validatedData['orbital_period'],
            'distance' => $validatedData['distance'],
            'surface_temperature' => $validatedData['surface_temperature'],
            'atmosphere_pressure' => $validatedData['atmosphere_pressure'],
            'orbital_eccentricity' => $validatedData['orbital_eccentricity'],
            'ml_disposition' => $mlResult['ml_disposition'] ?? 'Candidate',
            'habitability_score' => $mlResult['habitability_score'] ?? 0,
            'planet_type' => $mlResult['planet_type'] ?? 'Unknown',
        ]);

        // 4. تنسيق البيانات للـ JS (camelCase)
        $formattedData = [];
        foreach ($newPlanet->attributesToArray() as $key => $value) {
            $formattedData[Str::camel($key)] = $value;
        }

        $minId = Planet::min('id');
        $maxId = Planet::max('id');
        $formattedData['isFirst'] = $newPlanet->id == $minId;
        $formattedData['isLast'] = $newPlanet->id == $maxId;

        return response()->json($formattedData, 201);
    }



    // -------------------------------
    // عرض الصفحة الرئيسية مع أول/أحدث كوكب
    // -------------------------------
    public function index()
    {
        $planet = Planet::latest()->first(); 
        return view('welcome', compact('planet'));
    }
}
