<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'name',
        'quantity',
        'condition',
        'notes',
    ];

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
}
