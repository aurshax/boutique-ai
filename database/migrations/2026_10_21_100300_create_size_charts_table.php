<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('size_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 8);                    // 46, 48, 50 ... or S, M, L
            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });

        // Laravel's Blueprint has no int4range type — add it with raw Postgres SQL.
        DB::statement('ALTER TABLE size_charts ADD COLUMN height_cm int4range NOT NULL');
        DB::statement('ALTER TABLE size_charts ADD COLUMN waist_cm int4range NULL');
        DB::statement("ALTER TABLE size_charts ADD CONSTRAINT size_charts_height_sane CHECK (NOT isempty(height_cm) AND lower(height_cm) >= 120 AND upper(height_cm) <= 231)");

        // GiST index: "which size fits someone 178 cm tall?" → WHERE height_cm @> 178
        DB::statement('CREATE INDEX size_charts_height_gist ON size_charts USING gist (height_cm)');
    }

    public function down(): void
    {
        Schema::dropIfExists('size_charts');
    }
};
