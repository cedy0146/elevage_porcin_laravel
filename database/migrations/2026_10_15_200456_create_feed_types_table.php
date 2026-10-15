<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feed_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('stage');

            $table->string('unit')
                ->default('kg');

            $table->decimal('unit_price', 10, 2)
                ->default(0);

            $table->decimal('quantity', 12, 2)
                ->default(0);

            $table->decimal('minimum_threshold', 12, 2)
                ->default(0);

            $table->timestamps();

            $table->unique(['farm_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_types');
    }
};
