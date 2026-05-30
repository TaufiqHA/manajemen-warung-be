<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->integer('stock')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('warung_id');
            $table->index('category_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
