<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->timestamp('hora_entrada')->nullable();
            $table->timestamp('hora_salida')->nullable();
        });
    }

    public function down()
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('hora_entrada');
            $table->dropColumn('hora_salida');
        });
    }

};
