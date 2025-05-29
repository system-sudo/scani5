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
        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('risk')->nullable();
            $table->integer('social_score')->default(0);
            $table->enum('severity', ['low', 'medium', 'high','critical'])->nullable();
            $table->timestamp('first_seen')->comment('published date');
            $table->timestamp('last_identified_on')->comment('');
            $table->json('CVEs')->nullable();
            $table->enum('patch_priority', ['low', 'medium', 'high','critical'])->nullable();
            $table->string('impact')->nullable();
            $table->text('solution')->nullable();
            $table->text('workaround')->nullable();
            $table->string('result')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0-UnPatched, 1-Patched');
            $table->timestamps();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vulnerabilities');
    }
};
