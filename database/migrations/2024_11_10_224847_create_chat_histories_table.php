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
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->uuid('guid')->primary();
            $table->char('topic_guid', 50);
            $table->char('user_id', 50);
            $table->longText('message');
            $table->integer('page');
            $table->float('cosine_similarity')->nullable();
            $table->enum('sender', ['user', 'bot', 'cosine', 'openai']);
            $table->char('question_guid', 50);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('topic_guid')->references('guid')->on('topics')->onDelete('cascade');
            $table->foreign('question_guid')->references('guid')->on('questions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
