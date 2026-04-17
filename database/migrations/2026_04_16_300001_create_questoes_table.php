<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questoes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->smallInteger('numero');
            $table->text('enunciado')->nullable();
            $table->boolean('anulada')->default(false);
            $table->decimal('peso', 4, 2)->default(1.00);
            $table->timestampsTz();

            $table->unique(['prova_id', 'numero']);
            $table->index('prova_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questoes');
    }
};
