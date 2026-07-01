<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'corp_id',
        'zone_name',
        'zone_code',
        'description',
        'contact_person',
        'phone',
        'email',
        'address',
        'pincode',
        'total_wards',
        'status',
    ];

    protected $casts = [
        'total_wards' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the corporation that owns the zone
     */
    public function corporation()
    {
        return $this->belongsTo(Corporation::class, 'corp_id');
    }

    /**
     * Get the wards for the zone
     */
    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    /**
     * Scope for active zones
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
