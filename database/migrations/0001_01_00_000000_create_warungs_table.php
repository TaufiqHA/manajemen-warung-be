<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warungs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_url')->nullable();
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->boolean('is_tax_enabled')->default(false);
            $table->text('receipt_footer')->nullable();
            $table->string('currency')->default('IDR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warungs');
    }
};
