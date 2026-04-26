<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'assigned_to', 'assigned_by', 'division', 'priority', 'status'];

    public function scopeForDivision(Builder $query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('division', $user->division);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
