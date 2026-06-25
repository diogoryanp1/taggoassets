<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movement_documents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_movement_id')->constrained('asset_movements')->cascadeOnDelete();
            $table->foreignId('private_document_id')->constrained('private_documents')->restrictOnDelete();
            $table->string('document_type', 48);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['asset_movement_id', 'private_document_id']);
            $table->index(['tenant_id', 'asset_movement_id']);
            $table->index(['tenant_id', 'document_type']);
            $table->index(['tenant_id', 'deactivated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movement_documents');
    }
};
