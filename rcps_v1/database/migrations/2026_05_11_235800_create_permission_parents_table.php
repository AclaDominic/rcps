<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permission_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('is_active', 1)->default('Y');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_parents');
    }
};
