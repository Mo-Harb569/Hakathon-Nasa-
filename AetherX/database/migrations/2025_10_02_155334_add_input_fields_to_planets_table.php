<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('planets', function (Blueprint $table) {
        $table->float('planet_mass')->nullable();
        $table->float('planet_radius')->nullable();
        $table->float('orbital_period')->nullable();
        $table->float('distance')->nullable();
        $table->float('surface_temperature')->nullable();
        $table->float('atmosphere_pressure')->nullable();
        $table->float('orbital_eccentricity')->nullable();
    });
}

/**
     * Reverse the migrations.
     */

public function down()
{
    Schema::table('planets', function (Blueprint $table) {
        $table->dropColumn([
            'planet_mass', 
            'planet_radius', 
            'orbital_period', 
            'distance',
            'surface_temperature',
            'atmosphere_pressure',
            'orbital_eccentricity'
        ]);
    });
}
};
