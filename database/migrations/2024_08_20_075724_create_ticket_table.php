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


        Schema::dropIfExists('ticketing_tool');

        Schema::create('ticketing_tool', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('type')->nullable();
            $table->json('values')->nullable();
            // $table->string('url')->nullable();
            // $table->string('key')->nullable(); 
            // $table->string('token')->nullable(); 
            // $table->string('listId', 100)->nullable(); 
            $table->foreign('organization_id')->references('id')->on('organizations')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticketing_tool');
    }
};