<?php

use App\Services\SignatureRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('signer_id')->constrained('users');
            $table->enum('status', array_column(SignatureRequestStatus::cases(), 'value'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};
