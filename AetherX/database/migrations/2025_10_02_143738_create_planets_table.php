<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planets', function (Blueprint $table) {
            $table->id();
            $table->json('input_data')->nullable(); // البيانات المرسلة للتحليل
            $table->string('ml_disposition')->nullable(); // تصنيف ML
            $table->float('habitability_score')->nullable(); // مؤشر قابلية العيش
            $table->string('planet_type')->nullable(); // نوع الكوكب
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planets');
    }
};
