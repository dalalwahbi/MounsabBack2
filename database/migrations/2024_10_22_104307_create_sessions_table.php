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
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();  // Session ID as the primary key
            $table->foreignId('user_id')->nullable()->index();  // Foreign key for user ID
            $table->string('ip_address', 45)->nullable();  // IPv4/IPv6 address
            $table->text('user_agent')->nullable();  // Browser/Device details
            $table->longText('payload');  // Session data
            $table->integer('last_activity')->index();  // Timestamp of last activity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
