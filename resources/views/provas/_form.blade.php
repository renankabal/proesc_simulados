<div>
    <label class="block text-sm font-medium text-gray-700">Título *</label>
    <input type="text" name="titulo" value="{{ old('titulo', $prova->titulo ?? '') }}"
        class="mt-1 w-full border rounded px-3 py-2 focus:ring-green-500 focus:border-green-500 @error('titulo') border-red-400 @enderror">
    @error('titulo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Disciplina</label>
        <input type="text" name="disciplina" value="{{ old('disciplina', $prova->disciplina ?? '') }}"
            class="mt-1 w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Turma</label>
        <input type="text" name="turma" value="{{ old('turma', $prova->turma ?? '') }}"
            class="mt-1 w-full border rounded px-3 py-2">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Total de Questões *</label>
        <input type="number" name="total_questoes" min="1" max="200"
            value="{{ old('total_questoes', $prova->total_questoes ?? 20) }}"
            class="mt-1 w-full border rounded px-3 py-2 @error('total_questoes') border-red-400 @enderror">
        @error('total_questoes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Nota Máxima *</label>
        <input type="number" name="nota_maxima" step="0.01" min="0"
            value="{{ old('nota_maxima', $prova->nota_maxima ?? '10.00') }}"
            class="mt-1 w-full border rounded px-3 py-2">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Data de Aplicação</label>
        <input type="date" name="data_aplicacao"
            value="{{ old('data_aplicacao', isset($prova) && $prova->data_aplicacao ? $prova->data_aplicacao->format('Y-m-d') : '') }}"
            class="mt-1 w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="mt-1 w-full border rounded px-3 py-2">
            @foreach (\App\Domain\Prova\Enums\StatusProva::cases() as $status)
            <option value="{{ $status->value }}"
                {{ old('status', $prova->status->value ?? 'rascunho') === $status->value ? 'selected' : '' }}>
                {{ $status->label() }}
            </option>
            @endforeach
        </select>
    </div>
</div>
