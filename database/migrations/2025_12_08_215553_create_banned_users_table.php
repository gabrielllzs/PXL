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
        Schema::create('banned_users', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('banned_at')->useCurrent();
            $table->timestamps();

            $table->index('visitor_id');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banned_users');
    }
};
