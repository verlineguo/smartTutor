<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('answer_pdf', function (Blueprint $table) {
        $table->json('page_references')->nullable()->after('retrieval_score');
    });
}

public function down()
{
    Schema::table('answer_pdf', function (Blueprint $table) {
        $table->dropColumn('page_references');
    });
}
};
