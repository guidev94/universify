<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateSiteUsersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_site_users')) {
            return;
        }

        Schema::create('siamfesp_site_users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('usuario')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('senha');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_site_users');
    }
}
