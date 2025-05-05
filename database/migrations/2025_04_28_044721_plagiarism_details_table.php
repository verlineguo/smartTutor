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
        Schema::create('plagiarism_details', function (Blueprint $table) {
            $table->uuid('guid')->primary();
            $table->uuid('plagiarism_guid');
            $table->text('student_text');
            $table->text('best_match')->nullable();
            $table->boolean('is_plagiarized');
            $table->float('weighted_score');
            $table->json('individual_scores');
            $table->timestamps();
            
            $table->foreign('plagiarism_guid')->references('guid')->on('plagiarism');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagiarism_details');

    }
};
