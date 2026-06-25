<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCustomFieldDefinition;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Organizations\Models\Location;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetSelectionController extends Controller
{
    public function types(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $this->authorize('viewAny', AssetType::class);
        $category = AssetCategory::query()->forTenant($currentTenant->id())->where('public_id', $request->string('category'))->firstOrFail();

        return response()->json(AssetType::query()->forTenant($currentTenant->id())->where('asset_category_id', $category->id)->where('is_active', true)->orderBy('name')->limit(100)->get(['public_id', 'name']));
    }

    public function models(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $this->authorize('viewAny', AssetModel::class);
        $brand = $request->string('brand')->toString();
        $type = $request->string('type')->toString();
        abort_unless($brand !== '', 422);
        $query = AssetModel::query()->forTenant($currentTenant->id())->whereHas('brand', fn ($query) => $query->where('public_id', $brand))->where('is_active', true);
        if ($type !== '') {
            $query->where(fn ($query) => $query->whereNull('asset_type_id')->orWhereHas('type', fn ($query) => $query->where('public_id', $type)));
        }

        return response()->json($query->orderBy('name')->limit(100)->get(['public_id', 'name', 'manufacturer_code']));
    }

    public function locations(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $this->authorize('viewAny', Location::class);
        $unit = $request->string('unit')->toString();
        abort_unless($unit !== '', 422);
        $user = $request->user();
        $query = Location::query()->forTenant($currentTenant->id())->whereHas('unit', fn ($query) => $query->where('public_id', $unit))->where('status', 'active');
        if (! $user->hasPermission($currentTenant->require(), 'organizations.update')) {
            $query->whereIn('organizational_unit_id', $user->organizationalUnits()->select('organizational_units.id'));
        }

        return response()->json($query->orderBy('name')->limit(100)->get(['public_id', 'name', 'type']));
    }

    public function customFields(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $this->authorize('viewAny', AssetCustomFieldDefinition::class);
        $category = AssetCategory::query()->forTenant($currentTenant->id())->where('public_id', $request->string('category'))->firstOrFail();

        return response()->json(AssetCustomFieldDefinition::query()->forTenant($currentTenant->id())->where('asset_category_id', $category->id)->where('is_active', true)->orderBy('sort_order')->limit(100)->get(['public_id', 'name', 'key', 'field_type', 'is_required', 'options']));
    }
}
