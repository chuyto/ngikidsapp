<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hijos', function (Blueprint $table) {
            $table->boolean('deleted_with_padre')->default(false);
        });

        // Los hijos que ya estaban en papelera junto con su padre deben
        // restaurarse con él tras el despliegue de esta columna.
        DB::table('hijos')
            ->whereNotNull('deleted_at')
            ->whereIn('padre_id', DB::table('padres')->whereNotNull('deleted_at')->select('id'))
            ->update(['deleted_with_padre' => true]);
    }

    public function down(): void
    {
        Schema::table('hijos', function (Blueprint $table) {
            $table->dropColumn('deleted_with_padre');
        });
    }
};
