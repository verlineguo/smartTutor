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
        Schema::create('similarity', function (Blueprint $table) {
            $table->uuid('guid')->primary();
            $table->char('user_answer_guid', 50);
            $table->enum('llm_type', ['openai', 'gemini', 'deepseek']);
            $table->enum('algorithm', ['cosine', 'jaccard', 'bert', 'sequence_matcher', 'aho_corasick']);
            $table->float('similarity_score'); 
            $table->timestamps();

            $table->foreign('user_answer_guid')->references('guid')->on('chat_histories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('similarity');
    }
};
