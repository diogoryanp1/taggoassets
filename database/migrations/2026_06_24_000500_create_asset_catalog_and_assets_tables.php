<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->string('code', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'parent_id', 'name_normalized']);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'parent_id', 'is_active']);
        });

        Schema::create('asset_types', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->string('code', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_serial_number')->default(false);
            $table->boolean('requires_brand')->default(false);
            $table->boolean('requires_model')->default(false);
            $table->boolean('is_depreciable')->default(false);
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'asset_category_id', 'name_normalized']);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'asset_category_id', 'is_active']);
        });

        Schema::create('asset_brands', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name_normalized']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('asset_models', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_brand_id')->constrained('asset_brands')->restrictOnDelete();
            $table->foreignId('asset_type_id')->nullable()->constrained('asset_types')->nullOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->string('manufacturer_code', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'asset_brand_id', 'asset_type_id', 'name_normalized']);
            $table->index(['tenant_id', 'asset_brand_id', 'is_active']);
            $table->index(['tenant_id', 'asset_type_id', 'is_active']);
        });

        Schema::create('unit_of_measures', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_normalized');
            $table->string('symbol', 16);
            $table->string('symbol_normalized', 16);
            $table->string('type', 32);
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name_normalized']);
            $table->unique(['tenant_id', 'symbol_normalized']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('asset_conditions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        Schema::create('asset_custom_field_definitions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('key', 64);
            $table->string('field_type', 32);
            $table->boolean('is_required')->default(false);
            $table->jsonb('options')->nullable();
            $table->jsonb('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['asset_category_id', 'key']);
            $table->index(['tenant_id', 'asset_category_id', 'is_active']);
        });

        Schema::create('asset_number_sequences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('year')->nullable();
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
            $table->unique(['tenant_id', 'year']);
        });

        Schema::create('assets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_number', 64);
            $table->string('legacy_number', 64)->nullable();
            $table->string('description');
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->foreignId('asset_type_id')->constrained('asset_types')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('asset_brands')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('asset_models')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('condition_id')->constrained('asset_conditions')->restrictOnDelete();
            $table->string('status', 32);
            $table->foreignId('organizational_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->date('acquisition_date')->nullable();
            $table->unsignedBigInteger('acquisition_value_cents')->nullable();
            $table->string('serial_number', 128)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('custom_values')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'asset_number']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'asset_category_id']);
            $table->index(['tenant_id', 'asset_type_id']);
            $table->index(['tenant_id', 'organizational_unit_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'condition_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_number_sequences');
        Schema::dropIfExists('asset_custom_field_definitions');
        Schema::dropIfExists('asset_conditions');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('asset_models');
        Schema::dropIfExists('asset_brands');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('asset_categories');
    }
};
