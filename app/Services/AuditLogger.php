<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Registra una acción sensible sin acoplar los controladores a la tabla.
     * El actor se toma de la sesión actual.
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        self::write($action, $subject, $description, $oldValues, $newValues, auth()->id());
    }

    /**
     * Registra una acción atribuyéndola a un usuario explícito. Acepta null
     * para casos como el cierre de sesión después de eliminar la cuenta.
     */
    public static function logForUser(
        ?int $userId,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        self::write($action, $subject, $description, $oldValues, $newValues, $userId);
    }

    private static function write(
        string $action,
        ?Model $subject,
        ?string $description,
        array $oldValues,
        array $newValues,
        ?int $userId,
    ): void {
        $request = app()->runningInConsole() ? null : request();

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
