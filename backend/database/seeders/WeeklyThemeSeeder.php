<?php

namespace Database\Seeders;

use App\Models\WeeklyTheme;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class WeeklyThemeSeeder extends Seeder
{
    private const YEAR = 2026;

    private static function themes(): array
    {
        return [
            ['Underwater World',    'Dive deep into the ocean',              '🐠', '#0ea5e9'],
            ['Space Odyssey',       'Explore the cosmos and beyond',         '🚀', '#6d28d9'],
            ['Enchanted Forest',    'Magical woodland creatures and trees',  '🌿', '#059669'],
            ['City at Night',       'Urban neon lights and skylines',        '🌃', '#1e40af'],
            ['Cute Animals',        'Adorable pets and wildlife portraits',  '🐾', '#d97706'],
            ['Retro Vibes',         'Vintage 80s and 90s aesthetics',        '🕹️', '#7c3aed'],
            ['Mythical Creatures',  'Dragons, unicorns, and legends',        '🐉', '#dc2626'],
            ['Four Seasons',        'Nature through the turning year',       '🍂', '#b45309'],
            ['Food Art',            'Delicious dishes and sweet treats',     '🍕', '#f97316'],
            ['Superheroes',         'Comic book style heroes and powers',    '💥', '#dc2626'],
            ['Deep Sea',            'Mysterious marine life adventures',     '🐋', '#0284c7'],
            ['Dream Landscapes',    'Surreal dreamlike impossible worlds',   '✨', '#9333ea'],
            ['Robot Friends',       'Cute and futuristic mechanical pals',   '🤖', '#64748b'],
            ['Flower Power',        'Beautiful botanical and floral art',    '🌸', '#ec4899'],
            ['Ancient Wonders',     'Egypt, Rome, and ancient civilizations','🏛️', '#d97706'],
            ['Weather Phenomena',   'Storms, rainbows, and auroras',         '⚡', '#3b82f6'],
            ['Music & Sound',       'Instruments, beats, and melodies',      '🎵', '#8b5cf6'],
            ['Sports & Action',     'Athletes and motion frozen in art',     '⚽', '#22c55e'],
            ['Ocean Creatures',     'Colorful life beneath the waves',       '🐙', '#0369a1'],
            ['Horror & Mystery',    'Spooky, eerie, and unsettling scenes',  '👻', '#1f2937'],
            ['Cityscapes',          'Skylines, bridges, and architecture',   '🏙️', '#4f46e5'],
            ['Fairy Tales',         'Classic storybook enchanted scenes',    '🧚', '#f472b6'],
            ['Summer Vibes',        'Sun, beach, ice cream and fun',         '☀️', '#f59e0b'],
            ['Jungle Safari',       'Wild animals of the tropical jungle',   '🦁', '#84cc16'],
            ['Space Station',       'Life in zero gravity aboard a station', '🛸', '#818cf8'],
            ['Ancient Magic',       'Sorcerers, spellbooks, and potions',    '🔮', '#7c3aed'],
            ['Neon Dreams',         'Cyberpunk city nightlife vibes',        '💜', '#c026d3'],
            ['Mini Worlds',         'Tiny detailed microscopic landscapes',  '🌍', '#10b981'],
            ['Portrait Art',        'Faces, expressions, and emotion',       '🎭', '#f97316'],
            ['Mecha & Robots',      'Giant mechanical warriors and suits',   '⚙️', '#475569'],
            ['Botanical Art',       'Plants and flowers in fine detail',     '🌱', '#16a34a'],
            ['Pirates & Seas',      'Adventure and treasure on the seas',    '⚓', '#1e40af'],
            ['Pastel World',        'Soft, dreamy, and gentle colors',       '🎀', '#f9a8d4'],
            ['Underground',         'Cave dwellers, tunnels, and crystals',  '🦇', '#374151'],
            ['Sky High',            'Birds, clouds, balloons, and flight',   '🦅', '#93c5fd'],
            ['Cute Monsters',       'Friendly, loveable scary creatures',    '👾', '#a3e635'],
            ['Time Travel',         'Past, present, future collide',         '⌛', '#c084fc'],
            ['Village Life',        'Cozy countryside and village scenes',   '🏡', '#78350f'],
            ['Glowing Art',         'Bioluminescent and neon glowing life',  '✨', '#38bdf8'],
            ['Festival Lights',     'Celebrations, fireworks, and joy',      '🎆', '#ef4444'],
            ['Surreal Art',         'Impossible dreamscape mashups',         '🌀', '#8b5cf6'],
            ['Autumn Harvest',      'Fall colors, pumpkins, and harvest',    '🍁', '#d97706'],
            ['Steampunk',           'Victorian machines, gears, and smoke',  '⚙️', '#92400e'],
            ['Winter Magic',        'Snow, ice, and cozy fireside scenes',   '❄️', '#bfdbfe'],
            ['Night Sky',           'Stars, moon, and constellations',       '🌙', '#1e1b4b'],
            ['Pop Art',             'Bold colors, dots, and strong lines',   '🎨', '#ec4899'],
            ['Arctic Life',         'Polar bears, penguins, frozen tundra',  '🐻‍❄️', '#e0f2fe'],
            ['Toy Story',           'Childhood toys, games, and nostalgia',  '🎮', '#f97316'],
            ['Celestial Bodies',    'Suns, moons, planets, and comets',      '☀️', '#fbbf24'],
            ['Magical Portals',     'Doorways to other dimensions',          '🌀', '#7c3aed'],
            ['Year in Review',      'Best moments and memories of 2026',     '🎊', '#7c3aed'],
            ['New Year Dreams',     'Hopes and wishes for the year ahead',   '🌟', '#fbbf24'],
        ];
    }

    public function run(): void
    {
        $themes = self::themes();
        $weeksInYear = (int) Carbon::create(self::YEAR, 12, 28)->isoWeek();

        if (count($themes) < 52) {
            throw new RuntimeException('WeeklyThemeSeeder requires at least 52 themes.');
        }

        for ($weekNum = 1; $weekNum <= $weeksInYear; $weekNum++) {
            [$name, $desc, $emoji, $color] = $themes[($weekNum - 1) % count($themes)];

            $start = Carbon::create()->setISODate(self::YEAR, $weekNum, Carbon::MONDAY);
            $end   = $start->copy()->addDays(6);

            WeeklyTheme::updateOrCreate(
                ['week_number' => $weekNum, 'year' => self::YEAR],
                [
                    'theme_name'  => $name,
                    'description' => $desc,
                    'emoji'       => $emoji,
                    'color_hex'   => $color,
                    'starts_at'   => $start->toDateString(),
                    'ends_at'     => $end->toDateString(),
                ]
            );
        }
    }
}
