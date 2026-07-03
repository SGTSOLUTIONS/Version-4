<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Corporation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'state',
        'district',
        'pincode',
        'type',
        'status',
        'image',
        'boundary',
    ];

    // Raw GEOMETRY column is binary WKB — never JSON-safe.
    // Hide it from array/JSON output everywhere the model is serialized.
    protected $hidden = [
        'boundary',
    ];

    public function zones()
    {
        return $this->hasMany(Zone::class, 'corp_id');
    }
}
