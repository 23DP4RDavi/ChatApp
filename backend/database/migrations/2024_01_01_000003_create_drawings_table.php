<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('guest_name')->nullable();
            $table->string('title');
            $table->longText('drawing_data');
            $table->text('thumbnail')->nullable();
            $table->integer('votes_count')->default(0);
            $table->timestamps();

            $table->index(['created_at', 'votes_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
