<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternativas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('questao_id')->constrained('questoes')->cascadeOnDelete();
            $table->char('letra', 1);
            $table->text('texto')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['questao_id', 'letra']);
            $table->index('questao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};
