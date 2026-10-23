<?php

use App\Enums\FabricPattern;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->smallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 120);
            $table->string('composition', 160);           // masalan: "100% jun, Super 110s"
            $table->string('pattern', 32);
            $table->char('color_hex', 7);                 // asosiy rang, UI swatch uchun
            $table->string('swatch_image')->nullable();   // public/media/fabrics/...
            $table->timestamps();
        });

        // Postgres CHECK constraint — qiymatlar ro'yxati enum'dan olinadi.
        $patterns = "'".implode("','", FabricPattern::values())."'";
        DB::statement("ALTER TABLE fabrics ADD CONSTRAINT fabrics_pattern_check CHECK (pattern IN ({$patterns}))");
        DB::statement("ALTER TABLE fabrics ADD CONSTRAINT fabrics_color_hex_check CHECK (color_hex ~ '^#[0-9A-Fa-f]{6}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('fabrics');
        Schema::dropIfExists('styles');
    }
};
