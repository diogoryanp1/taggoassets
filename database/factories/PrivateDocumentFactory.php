<?php

namespace Database\Factories;

use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PrivateDocument> */
class PrivateDocumentFactory extends Factory
{
    protected $model = PrivateDocument::class;

    public function definition(): array
    {
        $name = fake()->bothify('document-####.pdf');

        return ['tenant_id' => Tenant::factory(), 'organizational_unit_id' => OrganizationalUnit::factory(), 'uploaded_by' => User::factory(), 'original_name' => $name, 'stored_name' => 'tenants/test/documents/'.Str::ulid().'.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 128, 'sha256' => hash('sha256', Str::uuid()->toString()), 'disk' => 'private'];
    }
}
