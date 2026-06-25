@csrf
<x-form-section title="Identificação" description="Dados usados nos termos e no histórico patrimonial.">
    <div class="grid gap-4 md:grid-cols-2">
        <x-input name="name" label="Responsável" value="{{ old('name', $custodian->name ?? '') }}" required />
        <x-input name="registration_number" label="Matrícula" value="{{ old('registration_number', $custodian->registration_number ?? '') }}" />
        <x-input name="document_identifier" label="Documento" value="{{ old('document_identifier', $custodian->document_identifier ?? '') }}" />
        <x-input name="position" label="Cargo" value="{{ old('position', $custodian->position ?? '') }}" />
        <x-input name="email" label="E-mail" value="{{ old('email', $custodian->email ?? '') }}" />
        <x-input name="phone" label="Telefone" value="{{ old('phone', $custodian->phone ?? '') }}" />
    </div>
</x-form-section>
<x-form-section title="Vínculos" description="O usuário é opcional, mas a unidade administrativa é obrigatória.">
    <div class="grid gap-4 md:grid-cols-2">
        <x-select name="organizational_unit" label="Unidade administrativa" required><option value="">Selecione</option>@foreach($organizationalUnits as $unit)<option value="{{ $unit->public_id }}" @selected(old('organizational_unit', $custodian->organizationalUnit->public_id ?? '') === $unit->public_id)>{{ $unit->name }}</option>@endforeach</x-select>
        <x-select name="user" label="Usuário vinculado"><option value="">Sem usuário vinculado</option>@foreach($users as $user)<option value="{{ $user->public_id }}" @selected(old('user', $custodian->user->public_id ?? '') === $user->public_id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</x-select>
    </div>
</x-form-section>
<div class="mt-5 flex gap-3"><x-button>Salvar alterações</x-button><a class="px-4 py-2 text-sm text-teal-700" href="{{ route('custodians.index') }}">Cancelar</a></div>
