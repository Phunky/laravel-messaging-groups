<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(messaging_table('groups'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->unique()
                ->constrained(messaging_table('conversations'))
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->json('meta')->nullable();
            $table->morphs('owner');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(messaging_table('groups'));
    }
};
