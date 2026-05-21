<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        static $schemaBootstrapChecked = false;
        static $adminBootstrapApplied = false;

        if (!$schemaBootstrapChecked) {
            $this->ensureCriticalSchema();
            $schemaBootstrapChecked = true;
        }

        if ($adminBootstrapApplied) {
            return;
        }

        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', ''));

        if ($email === '') {
            return;
        }

        try {
            if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'is_admin')) {
                return;
            }

            DB::table('users')
                ->where('email', $email)
                ->update(['is_admin' => true]);

            $adminBootstrapApplied = true;
        } catch (\Throwable $e) {
            // Ignore bootstrap promotion failures in production boot.
        }
    }

    private function ensureCriticalSchema(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }

            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable();
                }
                if (!Schema::hasColumn('users', 'google_id')) {
                    $table->string('google_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'auth_provider')) {
                    $table->string('auth_provider')->nullable();
                }
                if (!Schema::hasColumn('users', 'avatar_drawing_data')) {
                    $table->longText('avatar_drawing_data')->nullable();
                }
                if (!Schema::hasColumn('users', 'avatar_thumbnail')) {
                    $table->longText('avatar_thumbnail')->nullable();
                }
                if (!Schema::hasColumn('users', 'is_admin')) {
                    $table->boolean('is_admin')->default(false);
                }
            });

            if (!Schema::hasTable('drawings')) {
                Schema::create('drawings', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->string('title');
                    $table->longText('drawing_data');
                    $table->text('thumbnail')->nullable();
                    $table->integer('votes_count')->default(0);
                    $table->timestamps();
                    $table->index(['created_at', 'votes_count']);
                });
            }

            Schema::table('drawings', function (Blueprint $table) {
                if (!Schema::hasColumn('drawings', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('drawings', 'is_free')) {
                    $table->boolean('is_free')->default(false);
                }
                if (!Schema::hasColumn('drawings', 'theme_id')) {
                    $table->unsignedBigInteger('theme_id')->nullable();
                }
            });

            if (!Schema::hasTable('weekly_themes')) {
                Schema::create('weekly_themes', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedSmallInteger('week_number');
                    $table->unsignedSmallInteger('year');
                    $table->string('theme_name');
                    $table->text('description')->nullable();
                    $table->string('emoji')->default('art');
                    $table->string('color_hex')->default('#7c3aed');
                    $table->date('starts_at');
                    $table->date('ends_at');
                    $table->timestamps();
                    $table->unique(['week_number', 'year']);
                });
            }

            if (!Schema::hasTable('votes')) {
                Schema::create('votes', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('drawing_id');
                    $table->string('voter_identifier');
                    $table->timestamps();
                    $table->unique(['drawing_id', 'voter_identifier']);
                });
            }

            if (!Schema::hasTable('drawing_comments')) {
                Schema::create('drawing_comments', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('drawing_id');
                    $table->unsignedBigInteger('user_id');
                    $table->text('content');
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('conversations')) {
                return;
            }

            if (!Schema::hasTable('messages')) {
                Schema::create('messages', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('conversation_id');
                    $table->text('content')->nullable();
                    $table->json('drawing_data')->nullable();
                    $table->timestamps();
                    $table->index(['conversation_id', 'created_at']);
                });
            }

            Schema::table('messages', function (Blueprint $table) {
                if (!Schema::hasColumn('messages', 'channel_id')) {
                    $table->unsignedBigInteger('channel_id')->nullable();
                }
                if (!Schema::hasColumn('messages', 'reply_to_id')) {
                    $table->unsignedBigInteger('reply_to_id')->nullable();
                }
                if (!Schema::hasColumn('messages', 'reactions')) {
                    $table->json('reactions')->nullable();
                }
                if (!Schema::hasColumn('messages', 'is_pinned')) {
                    $table->boolean('is_pinned')->default(false);
                }
                if (!Schema::hasColumn('messages', 'edited_at')) {
                    $table->timestamp('edited_at')->nullable();
                }
            });

            Schema::table('conversations', function (Blueprint $table) {
                if (!Schema::hasColumn('conversations', 'name')) {
                    $table->string('name')->nullable();
                }
                if (!Schema::hasColumn('conversations', 'avatar_thumbnail')) {
                    $table->string('avatar_thumbnail')->nullable();
                }
                if (!Schema::hasColumn('conversations', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable();
                }
            });

            if (!Schema::hasTable('group_channels')) {
                Schema::create('group_channels', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('conversation_id')->nullable();
                    $table->string('name');
                    $table->string('type')->default('text');
                    $table->integer('position')->default(0);
                    $table->string('category')->default('Text Channels');
                    $table->json('allowed_role_ids')->nullable();
                    $table->timestamps();
                });
            } else {
                Schema::table('group_channels', function (Blueprint $table) {
                    if (!Schema::hasColumn('group_channels', 'category')) {
                        $table->string('category')->default('Text Channels');
                    }
                    if (!Schema::hasColumn('group_channels', 'allowed_role_ids')) {
                        $table->json('allowed_role_ids')->nullable();
                    }
                });
            }

            if (!Schema::hasTable('group_roles')) {
                Schema::create('group_roles', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('conversation_id')->nullable();
                    $table->string('name');
                    $table->string('color')->default('#99aab5');
                    $table->json('permissions')->nullable();
                    $table->integer('position')->default(0);
                    $table->boolean('is_default')->default(false);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('group_member_roles')) {
                Schema::create('group_member_roles', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('conversation_id')->nullable();
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->unsignedBigInteger('role_id')->nullable();
                    $table->timestamps();
                    $table->unique(['conversation_id', 'user_id', 'role_id']);
                });
            }

            if (!Schema::hasTable('group_invites')) {
                Schema::create('group_invites', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('conversation_id')->nullable();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->string('token', 12)->unique();
                    $table->timestamp('expires_at')->nullable();
                    $table->unsignedInteger('max_uses')->nullable();
                    $table->unsignedInteger('uses')->default(0);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('personal_access_tokens')) {
                Schema::create('personal_access_tokens', function (Blueprint $table) {
                    $table->id();
                    $table->morphs('tokenable');
                    $table->string('name');
                    $table->string('token', 64)->unique();
                    $table->text('abilities')->nullable();
                    $table->timestamp('last_used_at')->nullable();
                    $table->timestamp('expires_at')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Keep application booting even if schema self-heal cannot run.
        }
    }
}
