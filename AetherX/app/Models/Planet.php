<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planet extends Model
{
    use HasFactory;

    protected $guarded = []; 

    protected $fillable = [
    'name',
    'planet_mass',
    'planet_radius',
    'orbital_period',
    'distance',
    'planet_density',
    'physical_type',
    'ml_disposition',
    'habitability_score',
    'input_data',
    'surface_temperature',
    'atmosphere_pressure',
    'orbital_eccentricity'
];

}
