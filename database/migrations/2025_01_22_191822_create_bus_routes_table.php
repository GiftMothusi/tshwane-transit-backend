<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_number');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('stops');
            $table->decimal('fare', 8, 2);
            $table->boolean('is_express')->default(false);
            $table->integer('estimated_duration')->comment('Duration in minutes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_routes');
    }
};
