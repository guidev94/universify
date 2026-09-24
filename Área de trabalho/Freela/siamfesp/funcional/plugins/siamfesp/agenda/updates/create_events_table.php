<?php namespace Siamfesp\Agenda\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateEventsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_agenda_events')) return;
        Schema::create('siamfesp_agenda_events', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('event_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('link', 1024)->nullable();
            $table->string('image', 1024)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->index('event_date');
        });
    }
    public function down()
    {
        Schema::dropIfExists('siamfesp_agenda_events');
    }
}
