<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('type')->default('text'); // ✅ tipo de mensagem
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // ✅ histórico de mensagens apagadas

            $table->index(['room_id', 'recipient_id']);
            $table->index('sender_id');
            $table->index('created_at');
        });

        // Opcional: constraint check (dependente do DB)
        // DB::statement('ALTER TABLE messages ADD CONSTRAINT chk_message_target CHECK (room_id IS NOT NULL OR recipient_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
