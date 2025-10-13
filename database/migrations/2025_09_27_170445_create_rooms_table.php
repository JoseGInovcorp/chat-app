<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('name')->unique();
            $table->string('slug')->unique(); // ✅ identificador amigável
            $table->text('description')->nullable(); // ✅ descrição opcional
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // ✅ criador da sala
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
