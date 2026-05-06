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
            $table->double('execution_time', 8, 2)->nullable();
            $table->double('resource_utilization', 5, 2)->nullable();
            $table->double('scheduling_accuracy', 5, 2)->nullable();
            $table->integer('dependency_mode')->nullable();
            $table->timestamp('metrics_date')->nullable()->useCurrent();
        });

        Schema::create('ticket_algorithm_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->integer('dependency_mode')->nullable();
            $table->double('execution_time', 8, 2)->nullable();
            $table->double('resource_utilization', 5, 2)->nullable();
            $table->double('scheduling_accuracy', 5, 2)->nullable();
            $table->timestamp('metric_date')->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_algorithm_metrics');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'execution_time',
                'resource_utilization',
                'scheduling_accuracy',
                'dependency_mode',
                'metrics_date'
            ]);
        });
    }
};
