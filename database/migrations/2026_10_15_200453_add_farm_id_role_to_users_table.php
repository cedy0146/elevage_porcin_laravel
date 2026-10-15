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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('farm_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('role')
                ->default('technician');

            $table->boolean('is_active')
                ->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('farm_id');
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
