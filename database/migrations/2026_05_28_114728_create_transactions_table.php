<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_code', 50)->unique();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->enum('payment_method', ['CASH', 'TRANSFER', 'QRIS']);
            $table->unsignedBigInteger('paid_amount');
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->text('note')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('warung_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
