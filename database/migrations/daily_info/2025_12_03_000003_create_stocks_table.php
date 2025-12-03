<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('ticker_symbol');
            $table->decimal('price', 10, 2);
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('stocks');
    }
};
