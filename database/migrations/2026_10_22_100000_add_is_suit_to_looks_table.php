<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('looks', function (Blueprint $table) {
            // true — jacket and trousers share one fabric (a suit): the chosen fabric applies to both.
            // false — "separates": the fabric applies to the trousers only.
            $table->boolean('is_suit')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('looks', function (Blueprint $table) {
            $table->dropColumn('is_suit');
        });
    }
};
