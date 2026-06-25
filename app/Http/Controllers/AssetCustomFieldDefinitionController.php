<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCustomFieldDefinition;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetCustomFieldDefinitionRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetCustomFieldDefinitionController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetCustomFieldDefinition::class);
        $query = AssetCustomFieldDefinition::query()->forTenant($currentTenant->id())->with('category:id,name')->orderBy('sort_order')->orderBy('name');
        if ($request->filled('search')) {
            $query->where(fn ($query) => $query->where('name', 'ilike', '%'.$request->string('search').'%')->orWhere('key', 'ilike', '%'.$request->string('search').'%'));
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($query) => $query->where('public_id', $request->string('category')));
        }

        return view('catalog.custom-fields.index', ['fields' => $query->paginate($pagination->resolve($request))->withQueryString(), 'categories' => $this->categories($currentTenant)]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', AssetCustomFieldDefinition::class);

        return view('catalog.custom-fields.create', ['categories' => $this->categories($currentTenant)]);
    }

    public function store(StoreAssetCustomFieldDefinitionRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetCustomFieldDefinition::class);
        $tenant = $currentTenant->require();
        $payload = $this->payload($request->validated(), $tenant->id);
        $count = AssetCustomFieldDefinition::query()->forTenant($tenant->id)->where('asset_category_id', $payload['asset_category_id'])->count();
        abort_if($count >= (int) config('taggo.assets.custom_fields_limit', 50), 422, 'Limite de campos customizados atingido.');
        $this->rejectDuplicate($payload, $tenant->id);
        $field = DB::transaction(fn () => AssetCustomFieldDefinition::forceCreate($payload));
        $audit->record('asset_custom_field.created', $field, [], $field->only(['name', 'key', 'field_type', 'is_required', 'sort_order', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.custom-fields.show', $field)->with('success', 'Campo customizado cadastrado com sucesso.');
    }

    public function show(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($field, $currentTenant);
        $this->authorize('viewAny', AssetCustomFieldDefinition::class);
        $field->load('category:id,name');

        return view('catalog.custom-fields.show', compact('field'));
    }

    public function edit(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($field, $currentTenant);
        $this->authorize('update', $field);

        return view('catalog.custom-fields.edit', ['field' => $field, 'categories' => $this->categories($currentTenant)]);
    }

    public function update(StoreAssetCustomFieldDefinitionRequest $request, AssetCustomFieldDefinition $field, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardTenant($field, $currentTenant);
        $this->authorize('update', $field);
        $payload = $this->payload($request->validated(), $currentTenant->id());
        $this->rejectDuplicate($payload, $currentTenant->id(), $field->id);
        $hasValues = Asset::query()->forTenant($currentTenant->id())->where('asset_category_id', $field->asset_category_id)->whereNotNull("custom_values->{$field->key}")->exists();
        abort_if($hasValues && $field->field_type !== $payload['field_type'], 422, 'Tipo nao pode ser alterado porque o campo ja possui valores.');
        $old = $field->only(['name', 'key', 'field_type', 'is_required', 'sort_order', 'is_active']);
        DB::transaction(fn () => $field->update($payload));
        $audit->record('asset_custom_field.updated', $field, $old, $field->only(array_keys($old)));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.custom-fields.show', $field)->with('success', 'Campo customizado atualizado com sucesso.');
    }

    public function deactivate(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($field, $currentTenant, $audit, false, 'asset_custom_field.deactivated', 'Campo customizado inativado com sucesso.');
    }

    public function reactivate(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($field, $currentTenant, $audit, true, 'asset_custom_field.reactivated', 'Campo customizado reativado com sucesso.');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function payload(array $data, int $tenantId): array
    {
        $category = AssetCategory::query()->forTenant($tenantId)->where('public_id', $data['asset_category_id'])->firstOrFail();
        $key = filled($data['key'] ?? null) ? (string) $data['key'] : Str::slug((string) $data['name'], '_');
        $options = $data['field_type'] === 'select' ? array_values(array_filter($data['options'] ?? [], static fn ($value): bool => filled($value))) : null;
        abort_if($data['field_type'] === 'select' && count($options ?? []) === 0, 422, 'Campo select exige opcoes.');

        return ['tenant_id' => $tenantId, 'asset_category_id' => $category->id, 'name' => Str::squish((string) $data['name']), 'key' => Str::lower($key), 'field_type' => $data['field_type'], 'is_required' => request()->boolean('is_required'), 'options' => $options, 'validation_rules' => $data['validation_rules'] ?? null, 'sort_order' => (int) ($data['sort_order'] ?? 0), 'is_active' => request()->boolean('is_active', true)];
    }

    private function rejectDuplicate(array $payload, int $tenantId, ?int $ignoreId = null): void
    {
        $query = AssetCustomFieldDefinition::query()->forTenant($tenantId)->where('asset_category_id', $payload['asset_category_id'])->where('key', $payload['key']);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        abort_if($query->exists(), 422, 'Chave duplicada na categoria.');
    }

    private function toggle(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardTenant($field, $currentTenant);
        $this->authorize('deactivate', $field);
        DB::transaction(fn () => $field->update(['is_active' => $active]));
        $audit->record($action, $field);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    private function guardTenant(AssetCustomFieldDefinition $field, CurrentTenant $currentTenant): void
    {
        abort_unless($field->tenant_id === $currentTenant->id(), 404);
    }

    private function categories(CurrentTenant $currentTenant)
    {
        return AssetCategory::query()->forTenant($currentTenant->id())->orderBy('name')->get(['id', 'public_id', 'name']);
    }
}
