<?php

namespace Database\Seeders;

use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }
        $permissions = [
            'dashboard.view', 'users.view', 'users.create', 'users.update', 'users.block', 'organizations.view', 'organizations.create', 'organizations.update',
            'assets.view', 'assets.create', 'assets.update', 'assets.approve', 'assets.transfer', 'assets.delete_draft', 'assets.deactivate', 'assets.reactivate', 'assets.manage', 'assets.set_manual_number',
            'audit.view', 'settings.manage',
            'asset_categories.view', 'asset_categories.create', 'asset_categories.update', 'asset_categories.deactivate', 'asset_categories.reactivate', 'asset_categories.manage',
            'asset_types.view', 'asset_types.create', 'asset_types.update', 'asset_types.deactivate', 'asset_types.reactivate', 'asset_types.manage',
            'asset_brands.view', 'asset_brands.create', 'asset_brands.update', 'asset_brands.deactivate', 'asset_brands.reactivate', 'asset_brands.manage',
            'asset_models.view', 'asset_models.create', 'asset_models.update', 'asset_models.deactivate', 'asset_models.reactivate', 'asset_models.manage',
            'units_of_measure.view', 'units_of_measure.manage', 'units_of_measure.reactivate',
            'asset_conditions.view', 'asset_conditions.manage', 'asset_conditions.reactivate',
            'asset_custom_fields.view', 'asset_custom_fields.manage', 'asset_custom_fields.reactivate',
            'asset_custodians.view', 'asset_custodians.create', 'asset_custodians.update', 'asset_custodians.deactivate',
            'asset_movements.view', 'asset_movements.create', 'asset_movements.approve', 'asset_movements.reject', 'asset_movements.cancel', 'asset_movements.complete',
            'asset_terms.view', 'asset_terms.generate', 'asset_terms.download',
            'asset_movement_documents.view', 'asset_movement_documents.upload', 'asset_movement_documents.download', 'asset_movement_documents.deactivate',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission], ['label' => $permission]);
        }
        $map = [
            'super_admin' => $permissions,
            'tenant_admin' => $permissions,
            'manager' => ['dashboard.view', 'users.view', 'organizations.view', 'organizations.create', 'organizations.update', 'assets.view', 'assets.create', 'assets.update', 'assets.approve', 'assets.transfer', 'asset_categories.view', 'asset_categories.create', 'asset_categories.update', 'asset_types.view', 'asset_types.create', 'asset_types.update', 'asset_brands.view', 'asset_brands.create', 'asset_brands.update', 'asset_models.view', 'asset_models.create', 'asset_models.update', 'units_of_measure.view', 'asset_conditions.view', 'asset_custom_fields.view', 'asset_custodians.view', 'asset_custodians.create', 'asset_custodians.update', 'asset_movements.view', 'asset_movements.create', 'asset_movements.approve', 'asset_movements.reject', 'asset_movements.cancel', 'asset_movements.complete', 'asset_terms.view', 'asset_terms.generate', 'asset_terms.download', 'asset_movement_documents.view', 'asset_movement_documents.upload', 'asset_movement_documents.download', 'asset_movement_documents.deactivate'],
            'member' => ['dashboard.view', 'assets.view', 'assets.create', 'assets.update', 'asset_custodians.view', 'asset_movements.view', 'asset_movements.create', 'asset_movements.complete', 'asset_terms.view', 'asset_terms.download', 'asset_movement_documents.view', 'asset_movement_documents.upload', 'asset_movement_documents.download'],
            'auditor' => ['dashboard.view', 'assets.view', 'audit.view', 'asset_categories.view', 'asset_types.view', 'asset_brands.view', 'asset_models.view', 'units_of_measure.view', 'asset_conditions.view', 'asset_custom_fields.view', 'asset_custodians.view', 'asset_movements.view', 'asset_terms.view', 'asset_terms.download', 'asset_movement_documents.view', 'asset_movement_documents.download'],
            'viewer' => ['dashboard.view', 'assets.view', 'organizations.view', 'asset_categories.view', 'asset_types.view', 'asset_brands.view', 'asset_models.view', 'units_of_measure.view', 'asset_conditions.view', 'asset_custodians.view', 'asset_movements.view', 'asset_terms.view', 'asset_movement_documents.view'],
        ];
        $roles = [];
        foreach ($map as $name => $grants) {
            $role = Role::firstOrCreate(['name' => $name], ['label' => str_replace('_', ' ', ucfirst($name))]);
            $role->permissions()->sync(Permission::whereIn('name', $grants)->pluck('id'));
            $roles[$name] = $role;
        }
        foreach ([['Unidade', 'UN', 'unit', 0], ['Quilograma', 'KG', 'weight', 3], ['Metro', 'M', 'length', 2], ['Metro quadrado', 'M²', 'area', 2], ['Litro', 'L', 'volume', 3], ['Caixa', 'CX', 'unit', 0], ['Conjunto', 'CJ', 'unit', 0]] as [$name, $symbol, $type, $decimalPlaces]) {
            UnitOfMeasure::firstOrCreate(['tenant_id' => null, 'symbol_normalized' => strtolower($symbol)], ['name' => $name, 'name_normalized' => strtolower($name), 'symbol' => $symbol, 'type' => $type, 'decimal_places' => $decimalPlaces, 'is_system' => true, 'is_active' => true]);
        }
        foreach (['Novo', 'Ótimo', 'Bom', 'Regular', 'Ruim', 'Inservível'] as $order => $name) {
            AssetCondition::firstOrCreate(['tenant_id' => null, 'code' => str($name)->ascii()->lower()->replace(' ', '_')->toString()], ['name' => $name, 'sort_order' => $order, 'is_system' => true, 'is_active' => true]);
        }
        $tenant = Tenant::firstOrCreate(['slug' => 'demo-mombaca'], ['name' => 'Secretaria Municipal de Educação de Mombaça', 'status' => 'active', 'timezone' => 'America/Fortaleza', 'locale' => 'pt_BR']);
        User::firstOrCreate(['email' => 'superadmin@taggo.test'], ['name' => 'Superadministrador', 'password' => Hash::make('ChangeMe!12345'), 'status' => 'active', 'is_platform_admin' => true]);
        $root = OrganizationalUnit::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'SME'], ['type' => 'secretaria', 'name' => 'Secretaria Municipal de Educação', 'status' => 'active']);
        Location::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'SEDE'], ['organizational_unit_id' => $root->id, 'type' => 'prédio', 'name' => 'Sede Administrativa', 'status' => 'active']);
        foreach (['tenant_admin' => 'admin@taggo.test', 'manager' => 'manager@taggo.test', 'member' => 'member@taggo.test'] as $roleName => $email) {
            $user = User::firstOrCreate(['email' => $email], ['name' => ucfirst(explode('@', $email)[0]), 'password' => Hash::make('ChangeMe!12345'), 'status' => 'active']);
            $user->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $roles[$roleName]->id, 'status' => 'active']]);
        }
        if (app()->environment(['local', 'testing']) || config('taggo.seed_demo_data')) {
            $this->call(DemoTenantSeeder::class);
        }
    }
}
