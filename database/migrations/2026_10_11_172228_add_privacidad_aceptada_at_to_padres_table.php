<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidencia del consentimiento del Aviso de Privacidad al registrar.
     * Se sella con now() en PadreController@store (checkbox obligatorio).
     */
    public function up(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->timestamp('privacidad_aceptada_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn('privacidad_aceptada_at');
        });
    }
};
