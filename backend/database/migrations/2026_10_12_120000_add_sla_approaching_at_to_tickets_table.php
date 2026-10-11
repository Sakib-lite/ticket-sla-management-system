<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_approaching_at')->nullable()->after('sla_due_at');
            $table->index(['sla_status', 'sla_approaching_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['sla_status', 'sla_approaching_at']);
            $table->dropColumn('sla_approaching_at');
        });
    }
};
