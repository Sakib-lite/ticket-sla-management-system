<?php

use App\Enums\ImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('original_name');
            $table->string('disk', 32);
            $table->string('path');
            $table->string('status', 20)->default(ImportStatus::Pending->value);
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('kind', 20);
            $table->json('messages');
            $table->json('raw');
            $table->timestamp('created_at');

            $table->index(['import_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('imports');
    }
};
