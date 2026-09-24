<?php namespace Siamfesp\Lgpd\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateConsentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_lgpd_cookie_consents')) {
            return;
        }

        Schema::create('siamfesp_lgpd_cookie_consents', function ($table) {
            $table->increments('id');
            $table->string('consent_token', 80)->unique();
            $table->string('consent_type', 40)->default('custom');
            $table->string('categories', 255)->default('necessary');
            $table->text('preferences_json')->nullable();
            $table->string('source_page')->nullable();
            $table->string('ip_address', 60)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_lgpd_cookie_consents');
    }
}
