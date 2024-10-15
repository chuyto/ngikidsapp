<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailToPadresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->string('email')->unique()->nullable(); // Agregar el campo email
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn('email'); // Eliminar el campo email si es necesario revertir
        });
    }
}
