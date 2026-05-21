<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['conversation_id']);
            // Make the column nullable and re-add the constraint
            $table->unsignedBigInteger('conversation_id')->nullable()->change();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->unsignedBigInteger('conversation_id')->nullable(false)->change();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }
};
