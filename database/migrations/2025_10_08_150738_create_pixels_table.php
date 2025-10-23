<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up() {
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('x'); // This is fine for 0-499
            $table->unsignedSmallInteger('y'); // This is fine for 0-499
            $table->string('color', 7); // #RRGGBB
            $table->string('tx_signature')->unique(); // Solana tx signature
            $table->string('buyer_address');
            $table->timestamps();
            $table->unique(['x','y']);
        });
    }
    public function down() { Schema::dropIfExists('pixels'); }
};
