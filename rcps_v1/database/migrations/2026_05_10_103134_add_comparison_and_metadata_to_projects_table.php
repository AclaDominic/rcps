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
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'dependency_mode')) {
                $table->integer('dependency_mode')->nullable();
            }
            if (!Schema::hasColumn('projects', 'comparison_id')) {
                $table->string('comparison_id')->nullable();
            }
            if (!Schema::hasColumn('projects', 'metadata')) {
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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['dependency_mode', 'comparison_id', 'metadata']);
        });
    }
};
