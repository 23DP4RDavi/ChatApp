<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->nullOnDelete()->after('channel_id');
            $table->json('reactions')->nullable()->after('reply_to_id');
            $table->boolean('is_pinned')->default(false)->after('reactions');
            $table->timestamp('edited_at')->nullable()->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['reply_to_id', 'reactions', 'is_pinned', 'edited_at']);
        });
    }
};
