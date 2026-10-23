<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('looks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 64)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->smallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['style_id', 'sort']);
        });

        Schema::create('look_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('look_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // a product used in a look cannot be deleted
            $table->smallInteger('position')->default(0);

            $table->unique(['look_id', 'product_id']);
            $table->unique(['look_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('look_items');
        Schema::dropIfExists('looks');
    }
};
