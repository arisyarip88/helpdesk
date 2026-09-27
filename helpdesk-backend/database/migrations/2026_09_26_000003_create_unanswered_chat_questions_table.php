<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unanswered_chat_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('question_hash', 64)->unique();
            $table->unsignedInteger('occurrences')->default(0);
            $table->timestamp('last_asked_at')->nullable();
            $table->foreignId('knowledge_base_id')->nullable()->constrained('knowledge_bases')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unanswered_chat_questions');
    }
};