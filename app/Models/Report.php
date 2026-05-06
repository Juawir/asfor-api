<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    protected $fillable = [
        'title', 'division', 'date', 'budget', 'description',
        'attachment', 'status', 'submitted_by',
        'approved_by', 'approved_at', 'rejection_note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function scopeForDivision(Builder $query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('division', $user->division);
    }
}
