<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->integer('i');
            $table->integer('j');
            $table->string('color')->default('red');
            $table->timestamps();
            $table->unique(['i', 'j']); // Prevent duplicates
        });
    }
    public function down() { Schema::dropIfExists('pixels'); }
};
