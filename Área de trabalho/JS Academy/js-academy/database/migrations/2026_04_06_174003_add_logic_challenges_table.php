<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logic_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description');
            $table->text('task');
            $table->text('starter_code');
            $table->text('solution_regex')->nullable();
            $table->integer('xp_reward');
            $table->integer('diamond_reward');
            $table->string('difficulty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logic_challenges');
    }
};
