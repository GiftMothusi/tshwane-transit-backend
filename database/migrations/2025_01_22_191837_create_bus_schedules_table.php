<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('cascade');
            $table->time('departure_time');
            $table->enum('day_type', ['weekday', 'saturday', 'sunday']);
            $table->boolean('is_active')->default(true);
            $table->string('bus_number')->nullable();
            $table->integer('capacity')->default(60);
            $table->json('current_location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_schedules');
    }
};
