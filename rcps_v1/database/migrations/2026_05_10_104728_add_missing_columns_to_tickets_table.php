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
            if (!Schema::hasColumn('tickets', 'parent_ticket_id')) {
                $table->unsignedBigInteger('parent_ticket_id')->nullable();
                // Optional: add foreign key constraint
                // $table->foreign('parent_ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            }
            if (!Schema::hasColumn('tickets', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'metadata')) {
                $table->text('metadata')->nullable();
            }
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
            $table->dropColumn(['parent_ticket_id', 'start_date', 'due_date', 'metadata']);
        });
    }
};
