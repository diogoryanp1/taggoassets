<?php

namespace App\Domain\Assets\Services;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class AssetNumberGenerator
{
    public function generate(Tenant $tenant, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($tenant, $year): string {
            $sequence = DB::table('asset_number_sequences')->where('tenant_id', $tenant->id)->where('year', $year)->lockForUpdate()->first();
            if ($sequence === null) {
                DB::table('asset_number_sequences')->insert(['tenant_id' => $tenant->id, 'year' => $year, 'next_value' => 2, 'created_at' => now(), 'updated_at' => now()]);
                $value = 1;
            } else {
                $value = $sequence->next_value;
                DB::table('asset_number_sequences')->where('id', $sequence->id)->update(['next_value' => $value + 1, 'updated_at' => now()]);
            }

            return sprintf('PAT-%d-%06d', $year, $value);
        });
    }
}
