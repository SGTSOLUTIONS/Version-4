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
    public function zones()
    {
        return $this->hasMany(Zone::class, 'corp_id');
    }
}
