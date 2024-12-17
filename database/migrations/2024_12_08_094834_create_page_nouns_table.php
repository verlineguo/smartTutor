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
        Schema::create('page_nouns', function (Blueprint $table) {
            $table->uuid('guid')->primary();
            $table->char('topic_guid', 50);
            $table->string('language', 10);
            $table->integer('page');
            $table->string('noun');
            $table->float('cosine');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_nous');
    }
};
