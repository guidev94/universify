<?php namespace Siamfesp\Newsletter\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSubscribersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_newsletter_subscribers')) {
            return;
        }

        Schema::create('siamfesp_newsletter_subscribers', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('source_page')->nullable();
            $table->string('ip_address', 60)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_subscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_newsletter_subscribers');
    }
}
