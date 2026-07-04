<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ward extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wards';

    protected $fillable = [
        'zone_id',
        'ward_no',
        'drone_image',
        'extent_left',
        'extent_right',
        'extent_top',
        'extent_bottom',
        'boundary',
        'zone',
        'status',
        'contact_person',
        'designation',
        'phone',
        'email',
        'address'
    ];

    protected $casts = [
        'extent_left' => 'decimal:6',
        'extent_right' => 'decimal:6',
        'extent_top' => 'decimal:6',
        'extent_bottom' => 'decimal:6',
        'boundary' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the zone that owns the ward.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Scope for active wards.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive wards.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
