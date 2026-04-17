<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_processamento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('evento', 100);
            $table->string('nivel', 20)->default('info');
            $table->string('entidade', 100)->nullable();
            $table->uuid('entidade_id')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('payload')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('evento');
            $table->index(['entidade', 'entidade_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_processamento');
    }
};
