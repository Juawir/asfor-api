<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'status'];

    public function candidates()
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    public function votes()
    {
        return $this->hasMany(ElectionVote::class);
    }
}
