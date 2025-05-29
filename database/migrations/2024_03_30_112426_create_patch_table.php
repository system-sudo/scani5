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
        Schema::create('patch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vul_id');
            $table->longText('solution')->nullable();
            $table->text('description')->nullable();
            $table->enum('complexity', ['low', 'medium', 'high','critical'])->nullable();
            $table->string('url')->nullable();
            $table->string('type')->nullable();
            $table->json('os')->nullable();
            $table->enum('status', [0, 1, 2])->default(0)->comment('0-none, 1-Success, 2-Failed');
            $table->foreign('vul_id')->references('id')->on('vulnerabilities')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /*
     * Reverse the migrations.
     */


    public function down(): void
    {
        Schema::dropIfExists('patch');
    }
};
