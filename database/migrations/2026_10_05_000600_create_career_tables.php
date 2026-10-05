<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('location')->nullable();
            $table->string('website', 500)->default('');
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->json('role');
            $table->json('start')->nullable();
            $table->json('end')->nullable();
            $table->json('description')->nullable();
            $table->boolean('current')->default(false);
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('experience_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->json('text');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('education', function (Blueprint $table) {
            $table->id();
            $table->json('degree');
            $table->string('school')->default('');
            $table->string('period')->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('issuer')->default('');
            $table->string('date')->default('');
            $table->string('verify', 500)->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        foreach (['certifications', 'education', 'experience_duties', 'experiences', 'companies'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
