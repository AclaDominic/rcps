<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_parent_id')->nullable()->after('guard_name');
            
            $table->foreign('permission_parent_id')
                ->references('id')
                ->on('permission_parents')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['permission_parent_id']);
            $table->dropColumn('permission_parent_id');
        });
    }
};
