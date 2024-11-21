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
        Schema::create('grams', function (Blueprint $table) {
            $table->uuid('guid')->primary();
            $table->string('noun');
            $table->char('topic_guid', 50);
            $table->enum('gram_type', ['uni', 'bi', 'tri'])->nullable();
            $table->float('tfidf_val');
            $table->float('cosine_val');
            $table->enum('language', ['english', 'indonesia', 'japanese'])->nullable(); // Kolom bahasa
            $table->foreign('topic_guid')->references('guid')->on('topics')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grams');
    }
};
