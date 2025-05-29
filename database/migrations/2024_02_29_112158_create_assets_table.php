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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->bigInteger('host_id')->nullable();
            $table->string('host_name')->nullable();
            $table->bigInteger('resource_id')->nullable();
            $table->string('ip_address_v4')->nullable();
            $table->string('ip_address_v6')->nullable();
            $table->string('os')->nullable();
            $table->integer('rti_score')->nullable();
            $table->enum('severity', ['low', 'medium', 'high','critical'])->nullable();
            $table->string('comment')->nullable();
            $table->enum('type', ['workstation', 'server'])->nullable();
            $table->enum('agent_status', ['connected', 'disconnected'])->nullable();
            $table->timestamp('last_user_login')->nullable(); 
            $table->timestamp('last_scanned')->nullable(); 
            $table->timestamp('last_system_boot')->nullable();
            $table->timestamp('last_checked_in')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
