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
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_version_id')->constrained('formula_versions');
            $table->foreignId('contract_id')->constrained('contracts');
            $table->json('input_values');
            $table->json('calculation_steps');
            $table->decimal('result', 15, 4);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index('formula_version_id');
            $table->index('contract_id');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_calculations');
    }
};
