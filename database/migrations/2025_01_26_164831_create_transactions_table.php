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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['topup', 'ticket_purchase', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded']);
            $table->string('reference')->unique();
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['wallet_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
