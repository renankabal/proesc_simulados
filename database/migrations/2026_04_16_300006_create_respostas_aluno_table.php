<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respostas_aluno', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('leitura_id')->constrained('leituras')->cascadeOnDelete();
            $table->smallInteger('questao_numero');
            $table->char('marcacao', 1)->nullable();
            $table->boolean('dupla_marcacao')->default(false);
            $table->boolean('em_branco')->default(false);
            $table->decimal('confianca', 4, 3)->nullable();
            $table->boolean('corrigida_manual')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['leitura_id', 'questao_numero']);
            $table->index('leitura_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respostas_aluno');
    }
};
