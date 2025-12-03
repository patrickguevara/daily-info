<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('news_related_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->foreignId('weather_id')->nullable()->constrained('weather')->onDelete('cascade');
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->onDelete('cascade');
            $table->timestamps();

            $table->index('news_id');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('news_related_data');
    }
};
