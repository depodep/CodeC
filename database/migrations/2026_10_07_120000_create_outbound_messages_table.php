<?php

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
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('contact_number', 50)->index();
            $table->text('message');
            $table->string('sender_id', 50)->default('Scan-to-notify');
            $table->string('gateway_message_id', 150)->nullable()->index();
            $table->string('status', 30)->default('pending')->index(); // 'pending', 'sent', 'delivered', 'failed'
            $table->string('dispatch_mode', 50)->nullable();
            $table->text('api_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['contact_number', 'last_sent_at'], 'outbound_rate_limit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
