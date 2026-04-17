<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('disciplina', 100)->nullable();
            $table->string('turma', 100)->nullable();
            $table->smallInteger('ano_letivo')->nullable();
            $table->smallInteger('total_questoes')->default(10);
            $table->string('status', 30)->default('rascunho');
            $table->date('data_aplicacao')->nullable();
            $table->decimal('nota_maxima', 5, 2)->default(10.00);
            $table->timestampsTz();

            $table->index('user_id');
            $table->index('status');
            $table->index('data_aplicacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provas');
    }
};
