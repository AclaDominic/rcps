<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('main_task_id')->nullable();
            $table->unsignedBigInteger('parent_ticket_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['main_task_id', 'parent_ticket_id', 'start_date', 'due_date', 'metadata']);
        });
    }
};
