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
       Schema::create('ai_task_previews', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_uuid');
            $table->string('project_name');
            $table->string('main_task_name');
            $table->text('main_task_description');
            $table->integer('ai_subtask_count');
            $table->text('ai_description');
            $table->json('generated_tasks'); // Store the AI results as JSON
            $table->integer('total_hours');
            $table->string('session_id')->nullable(); // For temporary storage
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index('task_uuid');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_task_previews');
    }
};
