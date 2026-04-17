<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gabaritos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->smallInteger('versao')->default(1);
            $table->boolean('ativo')->default(true);
            $table->foreignUuid('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('respostas')->default('{}');
            $table->text('observacoes')->nullable();
            $table->timestampsTz();

            $table->index('prova_id');
            // Índice único parcial: apenas uma versão ativa por prova
            // Criado via raw SQL pois o Blueprint não suporta índice único parcial
        });

        DB::statement('CREATE UNIQUE INDEX idx_gabaritos_prova_ativo ON gabaritos(prova_id) WHERE ativo = TRUE');
    }

    public function down(): void
    {
        Schema::dropIfExists('gabaritos');
    }
};
