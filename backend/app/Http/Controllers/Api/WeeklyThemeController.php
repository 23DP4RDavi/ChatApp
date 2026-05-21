<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drawing;
use App\Models\WeeklyTheme;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class WeeklyThemeController extends Controller
{
    /** Pool of themes to randomly pick from each week */
    private static function themePool(): array
    {
        return [
            ['Underwater World',     'Dive deep into the ocean',              '🐠', '#0ea5e9'],
            ['Space Odyssey',        'Explore the cosmos and beyond',         '🚀', '#6d28d9'],
            ['Enchanted Forest',     'Magical woodland creatures and trees',  '🌿', '#059669'],
            ['City at Night',        'Urban neon lights and skylines',        '🌃', '#1e40af'],
            ['Cute Animals',         'Adorable pets and wildlife portraits',  '🐾', '#d97706'],
            ['Retro Vibes',          'Vintage 80s and 90s aesthetics',        '🕹️', '#7c3aed'],
            ['Mythical Creatures',   'Dragons, unicorns, and legends',        '🐉', '#dc2626'],
            ['Four Seasons',         'Nature through the turning year',       '🍂', '#b45309'],
            ['Food Art',             'Delicious dishes and sweet treats',     '🍕', '#f97316'],
            ['Superheroes',          'Comic book style heroes and powers',    '💥', '#ef4444'],
            ['Deep Sea',             'Mysterious marine life adventures',     '🐋', '#0284c7'],
            ['Dream Landscapes',     'Surreal dreamlike impossible worlds',   '✨', '#9333ea'],
            ['Robot Friends',        'Cute and futuristic mechanical pals',   '🤖', '#64748b'],
            ['Flower Power',         'Beautiful botanical and floral art',    '🌸', '#ec4899'],
            ['Ancient Wonders',      'Egypt, Rome, and lost civilizations',   '🏛️', '#d97706'],
            ['Weather Phenomena',    'Storms, rainbows, and auroras',         '⚡', '#3b82f6'],
            ['Music & Sound',        'Instruments, beats, and melodies',      '🎵', '#8b5cf6'],
            ['Sports & Action',      'Athletes and motion frozen in art',     '⚽', '#22c55e'],
            ['Ocean Creatures',      'Colorful life beneath the waves',       '🐙', '#0369a1'],
            ['Cityscapes',           'Skylines, bridges, and architecture',   '🏙️', '#4f46e5'],
            ['Fairy Tales',          'Classic storybook enchanted scenes',    '🧚', '#f472b6'],
            ['Summer Vibes',         'Sun, beach, ice cream and fun',         '☀️', '#f59e0b'],
            ['Jungle Safari',        'Wild animals of the tropical jungle',   '🦁', '#84cc16'],
            ['Space Station',        'Life in zero gravity',                  '🛸', '#818cf8'],
            ['Ancient Magic',        'Sorcerers, spellbooks, and potions',    '🔮', '#7c3aed'],
            ['Neon Dreams',          'Cyberpunk city nightlife vibes',        '💜', '#c026d3'],
            ['Mini Worlds',          'Tiny detailed microscopic landscapes',  '🌍', '#10b981'],
            ['Portrait Art',         'Faces, expressions, and emotion',       '🎭', '#f97316'],
            ['Mecha & Robots',       'Giant mechanical warriors and suits',   '⚙️', '#475569'],
            ['Botanical Art',        'Plants and flowers in fine detail',     '🌱', '#16a34a'],
            ['Pirates & Seas',       'Adventure and treasure on the seas',    '⚓', '#1e40af'],
            ['Pastel World',         'Soft, dreamy, and gentle colors',       '🎀', '#f9a8d4'],
            ['Underground',          'Cave dwellers, tunnels, and crystals',  '🦇', '#374151'],
            ['Sky High',             'Birds, clouds, balloons, and flight',   '🦅', '#93c5fd'],
            ['Cute Monsters',        'Friendly, loveable scary creatures',    '👾', '#a3e635'],
            ['Time Travel',          'Past, present, future collide',         '⌛', '#c084fc'],
            ['Village Life',         'Cozy countryside and village scenes',   '🏡', '#92400e'],
            ['Glowing Art',          'Bioluminescent and neon glowing life',  '✨', '#38bdf8'],
            ['Festival Lights',      'Celebrations, fireworks, and joy',      '🎆', '#ef4444'],
            ['Surreal Art',          'Impossible dreamscape mashups',         '🌀', '#8b5cf6'],
            ['Autumn Harvest',       'Fall colors, pumpkins, and harvest',    '🍁', '#d97706'],
            ['Steampunk',            'Victorian machines, gears, and smoke',  '⚙️', '#92400e'],
            ['Winter Magic',         'Snow, ice, and cozy fireside scenes',   '❄️', '#bfdbfe'],
            ['Night Sky',            'Stars, moon, and constellations',       '🌙', '#1e1b4b'],
            ['Pop Art',              'Bold colors, dots, and strong lines',   '🎨', '#ec4899'],
            ['Arctic Life',          'Polar bears, penguins, frozen tundra',  '🐻‍❄️', '#e0f2fe'],
            ['Toy Story',            'Childhood toys, games, and nostalgia',  '🎮', '#f97316'],
            ['Celestial Bodies',     'Suns, moons, planets, and comets',      '☀️', '#fbbf24'],
            ['Magical Portals',      'Doorways to other dimensions',          '🌀', '#7c3aed'],
            ['Horror & Mystery',     'Spooky, eerie, and unsettling scenes',  '👻', '#1f2937'],
            ['New Year Dreams',      'Hopes and wishes for the year ahead',   '🌟', '#fbbf24'],
        ];
    }

    /** Get (or auto-create) the current week's theme */
    public function current()
    {
        if (!Schema::hasTable('weekly_themes')) {
            return response()->json(['theme' => null]);
        }

        $theme = WeeklyTheme::where('starts_at', '<=', today())
            ->where('ends_at', '>=', today())
            ->first();

        if (!$theme) {
            $theme = $this->pickRandomTheme();
        }

        return response()->json(['theme' => $theme]);
    }

    /** All past theme weeks (only weeks that were actually played) */
    public function archive()
    {
        if (!Schema::hasTable('weekly_themes')) {
            return response()->json(['weeks' => []]);
        }

        $themes = WeeklyTheme::where('ends_at', '<', today())
            ->orderBy('starts_at', 'desc')
            ->get();

        return response()->json(['weeks' => $themes]);
    }

    /** Top 20 drawings submitted during a specific theme week */
    public function weekDrawings($weekNumber, $year)
    {
        if (!Schema::hasTable('weekly_themes')) {
            return response()->json(['theme' => null, 'drawings' => []]);
        }

        $theme = WeeklyTheme::where('week_number', $weekNumber)
            ->where('year', $year)
            ->firstOrFail();

        $query = Drawing::with('user')
            ->whereBetween('created_at', [
                $theme->starts_at->startOfDay(),
                $theme->ends_at->endOfDay(),
            ])
            ->orderBy('votes_count', 'desc')
            ->limit(20);

        if (Schema::hasTable('votes')) {
            $query->withCount('votes');
        }

        $drawings = $query->get();

        return response()->json([
            'theme'    => $theme,
            'drawings' => $drawings,
        ]);
    }

    /** Pick a random theme from the pool, avoiding recent repeats */
    private function pickRandomTheme(): WeeklyTheme
    {
        // Current ISO week + year
        $now      = Carbon::now();
        $weekNum  = (int) $now->isoWeek;
        $year     = (int) $now->isoWeekYear;
        $start    = $now->startOfWeek(Carbon::MONDAY)->toDateString();
        $end      = $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        // Names of themes used in the last 10 weeks (avoid repeats)
        $recentNames = WeeklyTheme::orderBy('starts_at', 'desc')
            ->limit(10)
            ->pluck('theme_name')
            ->toArray();

        $pool = collect(self::themePool())
            ->filter(fn($t) => !in_array($t[0], $recentNames))
            ->values()
            ->toArray();

        // Fallback to full pool if everything was recently used
        if (empty($pool)) {
            $pool = self::themePool();
        }

        $pick = $pool[array_rand($pool)];

        return WeeklyTheme::create([
            'week_number' => $weekNum,
            'year'        => $year,
            'theme_name'  => $pick[0],
            'description' => $pick[1],
            'emoji'       => $pick[2],
            'color_hex'   => $pick[3],
            'starts_at'   => $start,
            'ends_at'     => $end,
        ]);
    }
}
