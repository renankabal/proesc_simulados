<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leituras', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('cartao_id')->constrained('cartoes_resposta')->restrictOnDelete();
            $table->foreignUuid('lido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pendente');
            $table->string('imagem_path', 500)->nullable();
            $table->string('imagem_thumbnail', 500)->nullable();
            $table->jsonb('metadados_omr')->nullable();
            $table->string('origem', 30)->default('webcam');
            $table->timestampTz('confirmada_em')->nullable();
            $table->timestampsTz();

            $table->index('cartao_id');
            $table->index('status');
            $table->index('lido_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leituras');
    }
};
