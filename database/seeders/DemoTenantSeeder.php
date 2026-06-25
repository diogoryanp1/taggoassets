<?php

namespace Database\Seeders;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Models\AssetMovementDocument;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Assets\Services\AssetTermGenerator;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Identity\Models\Role;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $custodians = collect([
            ['name' => 'Ana Paula Ribeiro', 'registration' => 'MAT-1001', 'unit' => 'ADM', 'email' => 'ana.ribeiro@example.test', 'position' => 'Coordenadora administrativa'],
            ['name' => 'Carlos Henrique Souza', 'registration' => 'MAT-2001', 'unit' => 'TEC', 'email' => 'carlos.souza@example.test', 'position' => 'Analista de tecnologia'],
            ['name' => 'Marina Costa Lima', 'registration' => 'MAT-3001', 'unit' => 'ALM', 'email' => 'marina.lima@example.test', 'position' => 'Responsavel pelo almoxarifado'],
        ])->mapWithKeys(fn (array $data) => [$data['registration'] => AssetCustodian::updateOrCreate(
            ['tenant_id' => $tenant->id, 'registration_number' => $data['registration']],
            ['organizational_unit_id' => $units[$data['unit']]->id, 'name' => $data['name'], 'email' => $data['email'], 'position' => $data['position'], 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id]
        )]);

        Asset::query()->where('tenant_id', $tenant->id)->where('asset_number', 'PAT-DEMO-0001')->update(['custodian_id' => $custodians['MAT-1001']->id]);
        Asset::query()->where('tenant_id', $tenant->id)->where('asset_number', 'PAT-DEMO-0002')->update(['custodian_id' => $custodians['MAT-2001']->id]);
        Asset::query()->where('tenant_id', $tenant->id)->where('asset_number', 'PAT-DEMO-0004')->update(['custodian_id' => $custodians['MAT-1001']->id]);

        $movementSamples = [
            ['asset' => 'PAT-DEMO-0001', 'type' => 'initial_assignment', 'status' => 'completed', 'origin_unit' => 'ALM', 'dest_unit' => 'ADM', 'origin_location' => 'DEPOSITO', 'dest_location' => 'PREDIO', 'origin_custodian' => null, 'dest_custodian' => 'MAT-1001', 'reason' => 'Atribuicao inicial para uso administrativo.', 'effective_at' => now()->subDays(10)],
            ['asset' => 'PAT-DEMO-0002', 'type' => 'internal_transfer', 'status' => 'pending_approval', 'origin_unit' => 'TEC', 'dest_unit' => 'ADM', 'origin_location' => 'SALA-TEC', 'dest_location' => 'PREDIO', 'origin_custodian' => 'MAT-2001', 'dest_custodian' => 'MAT-1001', 'reason' => 'Transferencia solicitada para apoio temporario ao atendimento.', 'effective_at' => null],
            ['asset' => 'PAT-DEMO-0004', 'type' => 'loan', 'status' => 'completed', 'origin_unit' => 'ADM', 'dest_unit' => 'ADM', 'origin_location' => 'PREDIO', 'dest_location' => 'PREDIO', 'origin_custodian' => 'MAT-1001', 'dest_custodian' => 'MAT-1001', 'reason' => 'Emprestimo para reuniao externa.', 'expected_return_at' => now()->addDays(5), 'effective_at' => now()->subDay()],
            ['asset' => 'PAT-DEMO-0005', 'type' => 'temporary_checkout', 'status' => 'completed', 'origin_unit' => 'ALM', 'dest_unit' => 'ALM', 'origin_location' => 'DEPOSITO', 'dest_location' => 'DEPOSITO', 'origin_custodian' => null, 'dest_custodian' => 'MAT-3001', 'reason' => 'Saida temporaria vencida para avaliacao.', 'expected_return_at' => now()->subDays(2), 'effective_at' => now()->subDays(8)],
        ];
        foreach ($movementSamples as $sample) {
            $asset = Asset::query()->where('tenant_id', $tenant->id)->where('asset_number', $sample['asset'])->firstOrFail();
            AssetMovement::updateOrCreate(['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'movement_type' => $sample['type'], 'reason' => $sample['reason']], [
                'status' => $sample['status'],
                'origin_organizational_unit_id' => $units[$sample['origin_unit']]->id,
                'destination_organizational_unit_id' => $units[$sample['dest_unit']]->id,
                'origin_location_id' => $locations[$sample['origin_location']]->id,
                'destination_location_id' => $locations[$sample['dest_location']]->id,
                'origin_custodian_id' => $sample['origin_custodian'] ? $custodians[$sample['origin_custodian']]->id : null,
                'destination_custodian_id' => $sample['dest_custodian'] ? $custodians[$sample['dest_custodian']]->id : null,
                'requested_by' => $admin->id,
                'approved_by' => $sample['status'] === 'pending_approval' ? null : $admin->id,
                'approved_at' => $sample['status'] === 'pending_approval' ? null : now()->subDays(9),
                'effective_at' => $sample['effective_at'] ?? null,
                'expected_return_at' => $sample['expected_return_at'] ?? null,
                'metadata' => ['demo' => true],
            ]);
        }

        $termMovement = AssetMovement::query()->where('tenant_id', $tenant->id)->where('status', 'completed')->where('movement_type', 'initial_assignment')->first();
        if ($termMovement) {
            app(AssetTermGenerator::class)->generate($termMovement, $admin);
            foreach ([AssetMovementDocumentType::SignedTerm, AssetMovementDocumentType::Receipt] as $type) {
                $key = 'tenants/'.$tenant->public_id.'/movement-documents/demo/'.$termMovement->public_id.'-'.$type->value.'.pdf';
                if (! Storage::disk('private')->exists($key)) {
                    Storage::disk('private')->put($key, Pdf::loadHTML('<h1>'.$type->label().'</h1><p>Documento fictício de demonstração do Taggo Assets.</p>')->output());
                }
                $document = PrivateDocument::firstOrCreate(['tenant_id' => $tenant->id, 'sha256' => hash_file('sha256', Storage::disk('private')->path($key))], [
                    'organizational_unit_id' => $termMovement->destination_organizational_unit_id ?? $termMovement->origin_organizational_unit_id,
                    'uploaded_by' => $admin->id,
                    'original_name' => Str::slug($type->label().'-demo').'.pdf',
                    'stored_name' => $key,
                    'mime_type' => 'application/pdf',
                    'size_bytes' => Storage::disk('private')->size($key),
                    'disk' => 'private',
                ]);
                AssetMovementDocument::firstOrCreate(['asset_movement_id' => $termMovement->id, 'private_document_id' => $document->id], ['tenant_id' => $tenant->id, 'document_type' => $type->value, 'uploaded_by' => $admin->id]);
            }
        }

        if ($this->command) {
            $this->command->info('Demo tenant pronto: '.$tenant->name.' / '.$email);
        }
    }
}
