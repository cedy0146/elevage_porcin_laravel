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
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pig_lot_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('feed_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('stock_movement_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity', 10, 2);

            $table->decimal('unit_cost', 10, 2)
                ->default(0);

            $table->decimal('total_cost', 12, 2)
                ->default(0);

            $table->date('fed_on');

            $table->string('notes')
                ->nullable();

            $table->timestamps();

            $table->index(['pig_lot_id', 'fed_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedings');
    }
};
