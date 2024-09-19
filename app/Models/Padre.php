<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Padre extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'red', 'telefono', 'foto_padre', 'uuid_short'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($padre) {
            $padre->uuid_short = Str::upper(Str::random(6));
        });
    }

    public function hijos()
    {
        return $this->hasMany(Hijo::class);
    }
}

