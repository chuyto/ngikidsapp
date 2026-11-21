<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->string('wallet_serial', 191)->nullable()->after('email');
            $table->string('wallet_share_url', 512)->nullable()->after('wallet_serial');
            $table->timestamp('wallet_issued_at')->nullable()->after('wallet_share_url');
        });
    }

    public function down(): void
    {
        Schema::table('padres', function (Blueprint $table) {
            $table->dropColumn(['wallet_serial', 'wallet_share_url', 'wallet_issued_at']);
        });
    }
};
