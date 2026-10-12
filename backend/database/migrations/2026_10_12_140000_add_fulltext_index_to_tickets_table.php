<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->fullText(['subject', 'description']);
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropFullText(['subject', 'description']);
        });
    }

    private function supportsFullText(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
