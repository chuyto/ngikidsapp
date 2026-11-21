<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Traducción de las acciones registradas para mostrarlas en la
     * bitácora sin exponer los códigos internos al usuario.
     */
    public const ETIQUETAS = [
        'padre.creado' => 'Familia creada',
        'padre.actualizado' => 'Familia actualizada',
        'padre.privacidad_revocada' => 'Privacidad revocada',
        'padre.exportado' => 'Datos ARCO exportados',
        'padre.enviado_papelera' => 'Familia enviada a papelera',
        'padre.restaurado' => 'Familia restaurada',
        'padre.eliminado_definitivo' => 'Familia eliminada definitivamente',
        'padre.wallet_revocado' => 'Wallet revocado',
        'hijo.creado' => 'Niño creado',
        'hijo.actualizado' => 'Niño actualizado',
        'hijo.enviado_papelera' => 'Niño enviado a papelera',
        'hijo.restaurado' => 'Niño restaurado',
        'hijo.eliminado_definitivo' => 'Niño eliminado definitivamente',
        'asistencia.corregida' => 'Asistencia corregida',
        'asistencia.anulada' => 'Asistencia anulada',
        'supervisor.creado' => 'Supervisor creado',
        'supervisor.actualizado' => 'Supervisor actualizado',
        'supervisor.activado' => 'Supervisor activado',
        'supervisor.desactivado' => 'Supervisor desactivado',
        'supervisor.eliminado' => 'Supervisor eliminado',
        'usuario.creado' => 'Usuario creado',
        'usuario.actualizado' => 'Usuario actualizado',
        'usuario.eliminado' => 'Usuario eliminado',
        'sesion.iniciada' => 'Inicio de sesión',
        'sesion.cerrada' => 'Cierre de sesión',
        'sesion.fallida' => 'Inicio de sesión fallido',
        'reporte.pdf' => 'Reporte PDF descargado',
        'reporte.csv' => 'Reporte CSV descargado',
        'auditoria.exportada' => 'Auditoría exportada',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function etiquetaDe(string $action): string
    {
        return self::ETIQUETAS[$action]
            ?? Str::of($action)->replace(['.', '_'], ' ')->headline()->toString();
    }

    public function etiqueta(): string
    {
        return self::etiquetaDe($this->action);
    }

    public function entidad(): ?string
    {
        if (! $this->auditable_type) {
            return null;
        }

        return match (class_basename($this->auditable_type)) {
            'Padre' => 'Familia',
            'Hijo' => 'Niño',
            'Asistencia' => 'Asistencia',
            'Supervisor' => 'Supervisor',
            'User' => 'Usuario',
            default => class_basename($this->auditable_type),
        };
    }

    public function tono(): string
    {
        return match (true) {
            str_contains($this->action, 'eliminado'),
            str_contains($this->action, 'anulada'),
            str_contains($this->action, 'revocada'),
            str_contains($this->action, 'fallida') => 'badge-danger',
            str_contains($this->action, 'creado'),
            str_contains($this->action, 'iniciada'),
            str_contains($this->action, 'restaurado'),
            str_contains($this->action, 'activado') => 'badge-success',
            str_contains($this->action, 'actualizado'),
            str_contains($this->action, 'corregida') => 'badge-info',
            str_contains($this->action, 'exportad'),
            str_contains($this->action, 'descargado') => 'badge-primary',
            str_contains($this->action, 'papelera'),
            str_contains($this->action, 'desactivado'),
            str_contains($this->action, 'cerrada') => 'badge-warning',
            default => 'badge-neutral',
        };
    }
}
