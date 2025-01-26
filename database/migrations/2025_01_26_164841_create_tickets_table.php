<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->enum('status', ['active', 'used', 'expired', 'cancelled']);
            $table->string('qr_code')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Add indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'valid_until']);
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
