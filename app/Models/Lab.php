<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function pics()
    {
        return $this->belongsToMany(User::class, 'lab_user');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }
}
