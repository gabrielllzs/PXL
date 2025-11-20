<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->integer('x');
            $table->integer('y');
            $table->string('visitor_id');
            $table->string('color')->default('black');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('pixels'); }
};
