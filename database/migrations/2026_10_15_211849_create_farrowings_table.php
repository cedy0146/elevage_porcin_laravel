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
        Schema::create('farrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mating_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('sow_id')->constrained('breeders')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('farrowed_on');
            $table->unsignedInteger('born_alive');
            $table->unsignedInteger('stillborn')->default(0);
            $table->unsignedInteger('weaned_count')->nullable();
            $table->date('weaned_on')->nullable();
            $table->foreignId('pig_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farrowings');
    }
};
