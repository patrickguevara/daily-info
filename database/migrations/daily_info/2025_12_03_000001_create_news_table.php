<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('news', function (Blueprint $table) {
            $table->id();
            $table->string('headline');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('source');
            $table->timestamp('published_at');
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('news');
    }
};
