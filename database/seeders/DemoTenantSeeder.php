<?php

namespace Database\Seeders;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Identity\Models\Role;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! config('taggo.seed_demo_data')) {
            return;
        }

        if (! app()->environment(['local', 'testing']) && ! config('taggo.seed_demo_data')) {
            return;
        }

        $tenant = Tenant::updateOrCreate(['slug' => 'taggo-demo'], ['name' => 'Taggo Assets Demonstracao', 'status' => 'active', 'timezone' => 'America/Sao_Paulo', 'locale' => 'pt_BR']);
        $role = Role::where('name', 'tenant_admin')->firstOrFail();
        $email = (string) config('taggo.demo_admin_email', 'admin@taggo.local');
        $password = config('taggo.demo_admin_password');
        if (! $password) {
            $password = Str::password(18);
            if ($this->command && app()->environment(['local', 'testing'])) {
                $this->command->warn('Senha temporaria do administrador demo: '.$password);
            }
        }

        $admin = User::firstOrNew(['email' => $email]);
        $admin->forceFill(['name' => 'Administrador de Demonstracao', 'password' => Hash::make($password), 'status' => 'active', 'email_verified_at' => now()]);
        $admin->save();
        $admin->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $role->id, 'status' => 'active']]);

        $units = collect([
            ['code' => 'ADM', 'name' => 'Secretaria Administrativa', 'type' => 'secretaria'],
            ['code' => 'TEC', 'name' => 'Setor de Tecnologia', 'type' => 'setor'],
            ['code' => 'ALM', 'name' => 'Almoxarifado Central', 'type' => 'almoxarifado'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => OrganizationalUnit::updateOrCreate(['tenant_id' => $tenant->id, 'code' => $data['code']], ['name' => $data['name'], 'type' => $data['type'], 'status' => 'active'])]);
        $admin->organizationalUnits()->syncWithoutDetaching($units->pluck('id')->all());

        $locations = collect([
            ['code' => 'PREDIO', 'name' => 'Predio Principal', 'unit' => 'ADM', 'type' => 'predio'],
            ['code' => 'SALA-TEC', 'name' => 'Sala de Tecnologia', 'unit' => 'TEC', 'type' => 'sala'],
            ['code' => 'DEPOSITO', 'name' => 'Deposito', 'unit' => 'ALM', 'type' => 'deposito'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => Location::updateOrCreate(['tenant_id' => $tenant->id, 'code' => $data['code']], ['organizational_unit_id' => $units[$data['unit']]->id, 'name' => $data['name'], 'type' => $data['type'], 'status' => 'active'])]);

        $categories = collect(['INF' => 'Informatica', 'MOB' => 'Mobiliario', 'EQP' => 'Equipamentos'])->mapWithKeys(fn (string $name, string $code) => [$code => AssetCategory::updateOrCreate(['tenant_id' => $tenant->id, 'code' => $code], ['name' => $name, 'name_normalized' => Str::lower($name), 'is_active' => true])]);
        $types = collect([
            ['code' => 'COMP', 'name' => 'Computador', 'category' => 'INF', 'serial' => true, 'brand' => true, 'model' => true],
            ['code' => 'NOTE', 'name' => 'Notebook', 'category' => 'INF', 'serial' => true, 'brand' => true, 'model' => true],
            ['code' => 'IMPR', 'name' => 'Impressora', 'category' => 'EQP', 'serial' => true, 'brand' => true, 'model' => true],
            ['code' => 'MESA', 'name' => 'Mesa', 'category' => 'MOB', 'serial' => false, 'brand' => false, 'model' => false],
            ['code' => 'CAD', 'name' => 'Cadeira', 'category' => 'MOB', 'serial' => false, 'brand' => false, 'model' => false],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => AssetType::updateOrCreate(['tenant_id' => $tenant->id, 'code' => $data['code']], ['asset_category_id' => $categories[$data['category']]->id, 'name' => $data['name'], 'name_normalized' => Str::lower($data['name']), 'is_active' => true, 'requires_serial_number' => $data['serial'], 'requires_brand' => $data['brand'], 'requires_model' => $data['model'], 'is_depreciable' => true, 'default_useful_life_months' => 60])]);

        $brands = collect(['Dell', 'Lenovo', 'HP', 'Epson'])->mapWithKeys(fn (string $name) => [$name => AssetBrand::updateOrCreate(['tenant_id' => $tenant->id, 'name_normalized' => Str::lower($name)], ['name' => $name, 'is_active' => true])]);
        $models = collect([
            ['brand' => 'Dell', 'type' => 'NOTE', 'name' => 'Latitude 5440'],
            ['brand' => 'Lenovo', 'type' => 'COMP', 'name' => 'ThinkCentre M70q'],
            ['brand' => 'HP', 'type' => 'COMP', 'name' => 'ProDesk 400'],
            ['brand' => 'Epson', 'type' => 'IMPR', 'name' => 'EcoTank L3250'],
        ])->map(fn (array $data) => AssetModel::updateOrCreate(['tenant_id' => $tenant->id, 'asset_brand_id' => $brands[$data['brand']]->id, 'asset_type_id' => $types[$data['type']]->id, 'name_normalized' => Str::lower($data['name'])], ['name' => $data['name'], 'manufacturer_code' => Str::upper(Str::slug($data['name'], '-')), 'is_active' => true]));

        $unit = UnitOfMeasure::whereNull('tenant_id')->where('symbol_normalized', 'un')->firstOrFail();
        $condition = AssetCondition::whereNull('tenant_id')->where('code', 'bom')->first() ?? AssetCondition::whereNull('tenant_id')->where('is_active', true)->firstOrFail();
        $samples = [
            ['number' => 'PAT-DEMO-0001', 'description' => 'Notebook administrativo', 'type' => 'NOTE', 'brand' => 'Dell', 'model' => 'Latitude 5440', 'unit' => 'ADM', 'location' => 'PREDIO', 'status' => AssetStatus::Active],
            ['number' => 'PAT-DEMO-0002', 'description' => 'Desktop tecnologia', 'type' => 'COMP', 'brand' => 'Lenovo', 'model' => 'ThinkCentre M70q', 'unit' => 'TEC', 'location' => 'SALA-TEC', 'status' => AssetStatus::Active],
            ['number' => 'PAT-DEMO-0003', 'description' => 'Impressora multifuncional', 'type' => 'IMPR', 'brand' => 'Epson', 'model' => 'EcoTank L3250', 'unit' => 'TEC', 'location' => 'SALA-TEC', 'status' => AssetStatus::Draft],
            ['number' => 'PAT-DEMO-0004', 'description' => 'Mesa de reuniao', 'type' => 'MESA', 'brand' => null, 'model' => null, 'unit' => 'ADM', 'location' => 'PREDIO', 'status' => AssetStatus::Active],
            ['number' => 'PAT-DEMO-0005', 'description' => 'Cadeira operacional', 'type' => 'CAD', 'brand' => null, 'model' => null, 'unit' => 'ALM', 'location' => 'DEPOSITO', 'status' => AssetStatus::Inactive],
        ];
        foreach ($samples as $sample) {
            $type = $types[$sample['type']];
            $model = $sample['model'] ? $models->firstWhere('name', $sample['model']) : null;
            Asset::updateOrCreate(['tenant_id' => $tenant->id, 'asset_number' => $sample['number']], ['description' => $sample['description'], 'asset_category_id' => $type->asset_category_id, 'asset_type_id' => $type->id, 'brand_id' => $sample['brand'] ? $brands[$sample['brand']]->id : null, 'model_id' => $model?->id, 'unit_of_measure_id' => $unit->id, 'condition_id' => $condition->id, 'status' => $sample['status']->value, 'organizational_unit_id' => $units[$sample['unit']]->id, 'location_id' => $locations[$sample['location']]->id, 'serial_number' => $type->requires_serial_number ? 'SN-'.$sample['number'] : null, 'acquisition_date' => now()->subMonths(2)->toDateString(), 'acquisition_value_cents' => 250000, 'is_active' => $sample['status'] !== AssetStatus::Inactive, 'created_by' => $admin->id, 'updated_by' => $admin->id]);
        }

        if ($this->command) {
            $this->command->info('Demo tenant pronto: '.$tenant->name.' / '.$email);
        }
    }
}
