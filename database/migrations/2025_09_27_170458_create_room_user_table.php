<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->string('role')->default('member'); // ✅ role na sala
            $table->string('status')->default('active'); // ✅ estado do membro
            $table->timestamps();
            $table->softDeletes(); // ✅ histórico de membros removidos

            $table->unique(['room_id', 'user_id']);
            $table->index('invited_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_user');
    }
};
