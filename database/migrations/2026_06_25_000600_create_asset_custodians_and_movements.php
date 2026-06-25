<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_custodians', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organizational_unit_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('registration_number', 64)->nullable();
            $table->string('document_identifier', 128)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'registration_number']);
            $table->index(['tenant_id', 'organizational_unit_id', 'is_active']);
            $table->index(['tenant_id', 'user_id']);
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('custodian_id')->nullable()->after('location_id')->constrained('asset_custodians')->nullOnDelete();
            $table->index(['tenant_id', 'custodian_id']);
        });

        Schema::create('asset_movements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->string('movement_type', 48);
            $table->string('status', 32);
            $table->foreignId('origin_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('destination_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('origin_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('origin_custodian_id')->nullable()->constrained('asset_custodians')->nullOnDelete();
            $table->foreignId('destination_custodian_id')->nullable()->constrained('asset_custodians')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expected_return_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('related_movement_id')->nullable()->constrained('asset_movements')->nullOnDelete();
            $table->foreignId('term_document_id')->nullable()->constrained('private_documents')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'asset_id', 'created_at']);
            $table->index(['tenant_id', 'movement_type', 'status']);
            $table->index(['tenant_id', 'destination_organizational_unit_id']);
            $table->index(['tenant_id', 'destination_custodian_id']);
            $table->index(['tenant_id', 'expected_return_at', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('custodian_id');
        });
        Schema::dropIfExists('asset_custodians');
    }
};
