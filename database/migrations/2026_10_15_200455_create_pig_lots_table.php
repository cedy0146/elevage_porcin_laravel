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
        Schema::create('pig_lots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pen_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('code');

            $table->string('source')
                ->default('purchase');

            $table->unsignedInteger('initial_count');

            $table->decimal('initial_average_weight', 6, 2)
                ->default(0);

            $table->date('arrived_on');

            $table->date('expected_end_on');

            $table->date('closed_on')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(['farm_id', 'code']);

            $table->index(['farm_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pig_lots');
    }
};
