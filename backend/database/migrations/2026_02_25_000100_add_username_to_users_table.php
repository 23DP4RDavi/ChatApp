<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $user->name ?? 'user')) ?: 'user');
                $candidate = substr($base, 0, 45);
                $counter = 1;

                while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                    $candidate = substr($base, 0, 40) . $counter;
                    $counter++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
