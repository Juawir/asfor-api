<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    protected $fillable = ['title', 'division', 'date', 'budget', 'description', 'attachment', 'status'];

    public function scopeForDivision(Builder $query, $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('division', $user->division);
    }
}
