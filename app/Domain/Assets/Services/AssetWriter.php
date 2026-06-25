<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class AssetWriter
{
    public function __construct(private readonly AssetNumberGenerator $numbers) {}

    /** @param array<string, mixed> $input */
    public function create(Tenant $tenant, User $user, array $input, bool $maySetManualNumber): Asset
    {
        return DB::transaction(function () use ($tenant, $user, $input, $maySetManualNumber): Asset {
            $data = $this->resolve($tenant, $input, $maySetManualNumber);
            $data['tenant_id'] = $tenant->id;
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            return Asset::forceCreate($data);
        });
    }

    /** @param array<string, mixed> $input */
    public function update(Asset $asset, Tenant $tenant, User $user, array $input, bool $maySetManualNumber): Asset
    {
        return DB::transaction(function () use ($asset, $tenant, $user, $input, $maySetManualNumber): Asset {
            $data = $this->resolve($tenant, $input, $maySetManualNumber, $asset);
            $data['updated_by'] = $user->id;
            $asset->update($data);

            return $asset->refresh();
        });
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function resolve(Tenant $tenant, array $input, bool $maySetManualNumber, ?Asset $existing = null): array
    {
        $category = AssetCategory::query()->forTenant($tenant->id)->where('public_id', $input['asset_category_id'])->where('is_active', true)->firstOrFail();
        $type = AssetType::query()->forTenant($tenant->id)->where('public_id', $input['asset_type_id'])->where('is_active', true)->firstOrFail();
        abort_unless($type->asset_category_id === $category->id, 422, 'O tipo deve pertencer à categoria selecionada.');
        $brand = ! empty($input['brand_id']) ? AssetBrand::query()->forTenant($tenant->id)->where('public_id', $input['brand_id'])->where('is_active', true)->firstOrFail() : null;
        $model = ! empty($input['model_id']) ? AssetModel::query()->forTenant($tenant->id)->where('public_id', $input['model_id'])->where('is_active', true)->firstOrFail() : null;
        abort_unless($model === null || ($brand !== null && $model->asset_brand_id === $brand->id && ($model->asset_type_id === null || $model->asset_type_id === $type->id)), 422, 'O modelo não é compatível com a marca e o tipo selecionados.');
        abort_unless(! $type->requires_brand || $brand !== null, 422, 'Este tipo exige marca.');
        abort_unless(! $type->requires_model || $model !== null, 422, 'Este tipo exige modelo.');
        abort_unless(! $type->requires_serial_number || filled($input['serial_number'] ?? null), 422, 'Este tipo exige número de série.');

        $unit = UnitOfMeasure::query()->availableToTenant($tenant->id)->where('public_id', $input['unit_of_measure_id'])->where('is_active', true)->firstOrFail();
        $condition = AssetCondition::query()->availableToTenant($tenant->id)->where('public_id', $input['condition_id'])->where('is_active', true)->firstOrFail();
        $organizationalUnit = OrganizationalUnit::query()->forTenant($tenant->id)->where('public_id', $input['organizational_unit_id'])->where('status', 'active')->firstOrFail();
        $location = ! empty($input['location_id']) ? Location::query()->forTenant($tenant->id)->where('public_id', $input['location_id'])->where('status', 'active')->firstOrFail() : null;
        abort_unless($location === null || $location->organizational_unit_id === $organizationalUnit->id, 422, 'A localização deve pertencer à unidade selecionada.');

        $customValues = $this->validateCustomValues($category, $input['custom_values'] ?? []);
        $number = trim((string) ($input['asset_number'] ?? ''));
        if ($number === '' || ! $maySetManualNumber) {
            $number = $existing !== null ? $existing->asset_number : $this->numbers->generate($tenant);
        }

        return array_filter([
            'asset_number' => $number,
            'legacy_number' => Arr::get($input, 'legacy_number'),
            'description' => $input['description'],
            'asset_category_id' => $category->id,
            'asset_type_id' => $type->id,
            'brand_id' => $brand?->id,
            'model_id' => $model?->id,
            'unit_of_measure_id' => $unit->id,
            'condition_id' => $condition->id,
            'status' => $input['status'] ?? AssetStatus::Draft->value,
            'organizational_unit_id' => $organizationalUnit->id,
            'location_id' => $location?->id,
            'acquisition_date' => Arr::get($input, 'acquisition_date'),
            'acquisition_value_cents' => Arr::get($input, 'acquisition_value_cents'),
            'serial_number' => Arr::get($input, 'serial_number'),
            'notes' => Arr::get($input, 'notes'),
            'custom_values' => $customValues,
            'is_active' => ($input['status'] ?? AssetStatus::Draft->value) !== AssetStatus::Inactive->value,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function validateCustomValues(AssetCategory $category, array $values): array
    {
        $definitions = $category->customFields()->where('is_active', true)->get(['key', 'field_type', 'is_required', 'options']);
        $allowed = $definitions->pluck('key')->all();
        abort_unless(array_diff(array_keys($values), $allowed) === [], 422, 'Campo customizado inválido.');
        foreach ($definitions as $definition) {
            $value = $values[$definition->key] ?? null;
            abort_unless(! $definition->is_required || filled($value), 422, "O campo {$definition->key} é obrigatório.");
            if ($value === null) {
                continue;
            }
            if ($definition->field_type === 'integer') {
                abort_unless(filter_var($value, FILTER_VALIDATE_INT) !== false, 422, 'Valor customizado inteiro inválido.');
            }
            if ($definition->field_type === 'decimal') {
                abort_unless(is_numeric($value), 422, 'Valor customizado decimal inválido.');
            }
            if ($definition->field_type === 'boolean') {
                abort_unless(is_bool($value), 422, 'Valor customizado booleano inválido.');
            }
            if ($definition->field_type === 'select') {
                abort_unless(in_array($value, $definition->options ?? [], true), 422, 'Opção customizada inválida.');
            }
        }

        return $values;
    }
}
