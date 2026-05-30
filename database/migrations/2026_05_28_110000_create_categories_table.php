<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->index('warung_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
