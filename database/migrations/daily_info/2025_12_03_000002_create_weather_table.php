<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('weather', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->decimal('temperature', 5, 2);
            $table->string('description');
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('weather');
    }
};
