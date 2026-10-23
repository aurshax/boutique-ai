<?php

use App\Enums\ProductType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('type', 16)->index();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_uzs');      // UZS, whole units
            $table->string('image')->nullable();          // public/media/catalog/...
            $table->foreignId('default_fabric_id')->nullable()->constrained('fabrics')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $types = "'".implode("','", ProductType::values())."'";
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ({$types}))");
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_check CHECK (price_uzs >= 0)');

        // Which product can be made in which fabric (for fabric swaps).
        Schema::create('product_fabric', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fabric_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('extra_price_uzs')->default(0); // surcharge for this fabric
            $table->primary(['product_id', 'fabric_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_fabric');
        Schema::dropIfExists('products');
    }
};
