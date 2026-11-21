<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->string('privacidad_version', 50)->nullable();
            $table->string('privacidad_ip', 45)->nullable();
            $table->text('privacidad_user_agent')->nullable();
            $table->timestamp('privacidad_revocada_at')->nullable();
            $table->string('foto_padre_public_id')->nullable();
            $table->timestamp('wallet_revocado_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn([
                'privacidad_version',
                'privacidad_ip',
                'privacidad_user_agent',
                'privacidad_revocada_at',
                'foto_padre_public_id',
                'wallet_revocado_at',
            ]);
        });
    }
};
