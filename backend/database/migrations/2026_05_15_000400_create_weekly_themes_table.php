<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->string('theme_name');
            $table->text('description')->nullable();
            $table->string('emoji')->default('🎨');
            $table->string('color_hex')->default('#7c3aed');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamps();
            $table->unique(['week_number', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_themes');
    }
};
