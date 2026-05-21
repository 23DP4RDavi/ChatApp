<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('drawing_data');
            $table->text('thumbnail')->nullable();
            $table->integer('votes_count')->default(0);
            $table->timestamps();

            $table->index(['created_at', 'votes_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
