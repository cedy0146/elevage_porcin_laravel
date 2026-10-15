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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('feed_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('pig_lot_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');

            $table->decimal('quantity', 12, 2);

            $table->decimal('quantity_before', 12, 2);

            $table->decimal('quantity_after', 12, 2);

            $table->decimal('unit_cost', 10, 2)
                ->nullable();

            $table->string('reason')
                ->nullable();

            $table->timestamp('moved_at');

            $table->timestamps();

            $table->index(['feed_type_id', 'moved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
