<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hijo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'edad', 'padre_id'];

    public function padre()
    {
        return $this->belongsTo(Padre::class);
    }
}
