<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('looks', function (Blueprint $table) {
            // true — pidjak va shim bir matodan (kostyum): tanlangan tkan ikkalasiga qo'llanadi.
            // false — "separates": tkan faqat shimga.
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
