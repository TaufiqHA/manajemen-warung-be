<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->enum('category', [
                'BAHAN_BAKU', 'GAJI', 'LISTRIK', 'AIR', 'SEWA', 'PERALATAN', 'LAINNYA', 'BIAYA_OPERASIONAL',
            ]);
            $table->text('note')->nullable();
            $table->date('date');
            $table->timestamps();

            // Indexes
            $table->index('warung_id');
            $table->index('date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
