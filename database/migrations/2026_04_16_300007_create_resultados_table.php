<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('leitura_id')->constrained('leituras')->cascadeOnDelete();
            $table->foreignUuid('gabarito_id')->constrained('gabaritos')->restrictOnDelete();
            $table->smallInteger('total_questoes');
            $table->smallInteger('total_acertos')->default(0);
            $table->smallInteger('total_erros')->default(0);
            $table->smallInteger('total_brancos')->default(0);
            $table->smallInteger('total_anuladas')->default(0);
            $table->decimal('nota_bruta', 5, 2)->nullable();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->decimal('percentual_acerto', 5, 2)->nullable();
            $table->jsonb('detalhe_questoes')->nullable();
            $table->timestampTz('calculado_em')->useCurrent();
            $table->timestampTz('recalculado_em')->nullable();
            $table->smallInteger('versao_calculo')->default(1);
            $table->timestampsTz();

            $table->unique('leitura_id');
            $table->index('gabarito_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados');
    }
};
