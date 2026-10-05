<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->string('icon')->default('article');
            $table->date('date')->nullable();
            $table->unsignedSmallInteger('read_min')->default(1);
            $table->string('url', 500)->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['post_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        foreach (['post_tag', 'posts', 'tags'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
