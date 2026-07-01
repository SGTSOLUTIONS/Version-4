<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'corporation_id',
        'zone_id',
        'ward_id',
        'profile',
        'is_active',
        'email_verified_at',
        'team_leader_id', // The team leader this surveyor reports to
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function corporation()
    {
        return $this->belongsTo(Corporation::class, 'corporation_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    // Team Leader relationship
    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    // Surveyors under this team leader
    public function surveyors()
    {
        return $this->hasMany(User::class, 'team_leader_id')
            ->where('role', 'surveyor');
    }

    // Get all team members (surveyors) for a team leader
    public function getTeamMembers()
    {
        if (!$this->isTeamLeader()) {
            return collect();
        }
        return $this->surveyors()->where('is_active', true)->get();
    }

    // Get available surveyors (not assigned to any team leader)
    public function getAvailableSurveyors()
    {
        return User::where('role', 'surveyor')
            ->where('corporation_id', $this->corporation_id)
            ->where('zone_id', $this->zone_id)
            ->where('ward_id', $this->ward_id)
            ->where('is_active', true)
            ->whereNull('team_leader_id')
            ->get();
    }

    // Assign a surveyor to this team leader
    public function assignSurveyor($surveyorId)
    {
        $surveyor = User::where('role', 'surveyor')
            ->where('id', $surveyorId)
            ->whereNull('team_leader_id')
            ->first();

        if (!$surveyor) {
            return false;
        }

        $surveyor->team_leader_id = $this->id;
        return $surveyor->save();
    }

    // Remove a surveyor from this team
    public function removeSurveyor($surveyorId)
    {
        $surveyor = User::where('role', 'surveyor')
            ->where('id', $surveyorId)
            ->where('team_leader_id', $this->id)
            ->first();

        if (!$surveyor) {
            return false;
        }

        $surveyor->team_leader_id = null;
        return $surveyor->save();
    }

    // Check if a surveyor is in this team
    public function hasSurveyor($surveyorId)
    {
        return $this->surveyors()->where('id', $surveyorId)->exists();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCommissioner()
    {
        return $this->role === 'commissioner';
    }

    public function isDC()
    {
        return $this->role === 'dc';
    }

    public function isAC()
    {
        return $this->role === 'ac';
    }

    public function isARO()
    {
        return $this->role === 'aro';
    }

    public function isBC()
    {
        return $this->role === 'bc';
    }

    public function isTeamLeader()
    {
        return $this->role === 'teamleader';
    }

    public function isSurveyor()
    {
        return $this->role === 'surveyor';
    }

    public function activate()
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        return $this->save();
    }
}
