<?php

use App\Enums\SlaStatus;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->string('subject');
            $table->text('description');
            $table->string('priority', 20);
            $table->string('status', 32)->default(TicketStatus::Open->value);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('sla_due_at')->nullable();
            $table->string('sla_status', 20)->default(SlaStatus::Within->value);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('resolved_within_sla')->nullable();

            $table->string('external_reference')->nullable()->unique();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_agent_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['sla_status', 'sla_due_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
