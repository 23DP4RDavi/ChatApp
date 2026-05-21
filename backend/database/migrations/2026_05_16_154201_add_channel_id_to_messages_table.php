<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('channel_id')->nullable()->constrained('group_channels')->onDelete('set null')->after('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\GroupChannel::class, 'channel_id');
            $table->dropColumn('channel_id');
        });
    }
};
