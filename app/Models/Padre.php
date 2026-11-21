<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Padre extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'parentesco',
        'red_id',
        'telefono',
        'foto_padre',
        'uuid_short',
        'email',
        'wallet_serial',
        'wallet_share_url',
        'wallet_issued_at',
        'wallet_revocado_at',
        'privacidad_aceptada_at',
        'privacidad_version',
        'privacidad_ip',
        'privacidad_user_agent',
        'privacidad_revocada_at',
        'foto_padre_public_id',
    ];

    protected $casts = [
        'privacidad_aceptada_at' => 'datetime',
        'privacidad_revocada_at' => 'datetime',
        'wallet_issued_at' => 'datetime',
        'wallet_revocado_at' => 'datetime',
    ];

    public function red()
    {
        return $this->belongsTo(Red::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($padre) {
            if (! $padre->uuid_short) {
                do {
                    $codigo = Str::upper(Str::random(6));
                } while (self::withTrashed()->where('uuid_short', $codigo)->exists());

                $padre->uuid_short = $codigo;
            }
        });

        // Al enviar a papelera, sus hijos van tambien (soft). Al restaurar,
        // regresan solo los hijos que cayeron con el padre, no los que se
        // eliminaron individualmente antes. El borrado definitivo cae a la
        // FK en cascada.
        static::deleting(function ($padre) {
            if (! $padre->isForceDeleting()) {
                $padre->hijos()->update([
                    'deleted_at' => now(),
                    'deleted_with_padre' => true,
                ]);
            }
        });

        static::restoring(function ($padre) {
            $padre->hijos()->withTrashed()
                ->where('deleted_with_padre', true)
                ->update([
                    'deleted_at' => null,
                    'deleted_with_padre' => false,
                ]);
        });
    }

    public function hijos()
    {
        return $this->hasMany(Hijo::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'uuid_short', 'uuid_short');
    }
}
