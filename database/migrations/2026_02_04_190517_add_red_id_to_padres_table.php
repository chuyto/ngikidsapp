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
        Schema::table('padres', function (Blueprint $table) {
            $table->foreignId('red_id')
                ->nullable()
                ->constrained('redes')
                ->after('red');

            $table->dropColumn('red');
        });
    }

    public function down()
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->integer('red')->nullable();
            $table->dropForeign(['red_id']);
            $table->dropColumn('red_id');
        });
    }
};
