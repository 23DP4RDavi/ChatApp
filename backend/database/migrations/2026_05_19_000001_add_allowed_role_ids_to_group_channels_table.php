<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_channels', function (Blueprint $table) {
            $table->json('allowed_role_ids')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('group_channels', function (Blueprint $table) {
            $table->dropColumn('allowed_role_ids');
        });
    }
};
