<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->index(['tenant_id', 'organizational_unit_id', 'parent_id']);
            $table->index(['tenant_id', 'parent_id', 'status']);
        });

        Schema::create('private_documents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('disk', 32)->default('private');
            $table->timestamps();
            $table->unique(['tenant_id', 'sha256']);
            $table->index(['tenant_id', 'organizational_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_documents');
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'organizational_unit_id', 'parent_id']);
            $table->dropIndex(['tenant_id', 'parent_id', 'status']);
        });
    }
};
