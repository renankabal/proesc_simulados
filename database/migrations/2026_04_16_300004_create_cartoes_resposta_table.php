<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes_resposta', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('prova_id')->constrained('provas')->restrictOnDelete();
            $table->string('codigo_aluno', 100);
            $table->string('nome_aluno')->nullable();
            $table->string('turma', 100)->nullable();
            $table->smallInteger('tentativa')->default(1);
            $table->string('qr_data')->unique();
            $table->string('pdf_path', 500)->nullable();
            $table->timestampTz('gerado_em')->useCurrent();
            $table->foreignUuid('gerado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('prova_id');
            $table->index('codigo_aluno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes_resposta');
    }
};
