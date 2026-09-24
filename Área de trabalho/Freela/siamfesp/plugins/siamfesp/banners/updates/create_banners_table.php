<?php namespace Siamfesp\Banners\Updates;

use Carbon\Carbon;
use DB;
use Schema;
use October\Rain\Database\Updates\Migration;

class CreateBannersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_banners')) {
            return;
        }

        Schema::create('siamfesp_banners', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title_1');
            $table->string('title_2')->nullable();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->boolean('is_image_only')->default(false)->index();
            $table->string('button_text', 120)->nullable()->default('Saiba Mais');
            $table->string('button_link', 500)->nullable();
            $table->string('button_target', 20)->nullable()->default('_self');
            $table->boolean('is_published')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $now = Carbon::now();

        DB::table('siamfesp_banners')->insert([
            [
                'title_1'      => 'ASSOCIE-SE',
                'title_2'      => 'SIAMFESP',
                'description'  => 'Quem é associado tem muito mais vantagens! Desfrute de benefícios exclusivos e condições especiais.',
                'image'        => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2000&auto-format&fit=crop',
                'is_image_only'=> false,
                'button_text'  => 'Saiba Mais',
                'button_link'  => '#',
                'button_target'=> '_self',
                'is_published' => true,
                'sort_order'   => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'title_1'      => 'EVENTOS',
                'title_2'      => 'EXCLUSIVOS',
                'description'  => 'Participe de palestras, workshops e rodadas de negócios focadas no crescimento da sua indústria.',
                'image'        => 'https://images.unsplash.com/photo-1565043589221-1a6fd9ae45c7?q=80&w=2000&auto-format&fit=crop',
                'is_image_only'=> false,
                'button_text'  => 'Ver Agenda',
                'button_link'  => '#',
                'button_target'=> '_self',
                'is_published' => true,
                'sort_order'   => 2,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_banners');
    }
}
