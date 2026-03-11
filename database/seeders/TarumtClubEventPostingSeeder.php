<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TarumtClubEventPostingSeeder extends Seeder
{
    /**
     * Create 1 TAR UMT-style event + 1 posting (with image) for each demo club account.
     */
    public function run(): void
    {
        $now = now();
        $themes = [
            ['from' => '#0B4FA2', 'to' => '#1F8CE6', 'accent' => '#FBBF24'],
            ['from' => '#7C3AED', 'to' => '#A78BFA', 'accent' => '#34D399'],
            ['from' => '#0F766E', 'to' => '#14B8A6', 'accent' => '#F59E0B'],
            ['from' => '#BE123C', 'to' => '#FB7185', 'accent' => '#22D3EE'],
            ['from' => '#1D4ED8', 'to' => '#60A5FA', 'accent' => '#F97316'],
            ['from' => '#166534', 'to' => '#4ADE80', 'accent' => '#2563EB'],
            ['from' => '#7C2D12', 'to' => '#FB923C', 'accent' => '#6366F1'],
            ['from' => '#334155', 'to' => '#94A3B8', 'accent' => '#EAB308'],
            ['from' => '#9F1239', 'to' => '#F472B6', 'accent' => '#10B981'],
            ['from' => '#4338CA', 'to' => '#818CF8', 'accent' => '#F43F5E'],
        ];

        $eventTemplates = [
            [
                'name' => 'TAR UMT Orientation Survival Workshop',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Multipurpose Hall',
                'event_description' => 'A practical orientation session for new students covering campus systems, academic planning, and student life support channels.',
                'posting_description' => 'Join this practical orientation workshop to quickly understand TAR UMT academic systems and campus support services.',
            ],
            [
                'name' => 'TAR UMT Chinese Language Cultural Night',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Auditorium',
                'event_description' => 'An evening showcase of Chinese language performances and cultural sharing led by student organizers.',
                'posting_description' => 'Cultural performances, language sharing, and student networking in one TAR UMT community night.',
            ],
            [
                'name' => 'TAR UMT Engineering Design Sprint',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Engineering Lab',
                'event_description' => 'A short team challenge where participants design and pitch practical engineering concepts with mentor feedback.',
                'posting_description' => 'Work in teams, build a concept, and present your engineering idea with mentor guidance.',
            ],
            [
                'name' => 'TAR UMT Computing Hack Practice Session',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Computer Lab 3',
                'event_description' => 'Hands-on coding practice focused on problem-solving, debugging habits, and collaboration techniques.',
                'posting_description' => 'Practice coding challenges together and level up your teamwork and debugging workflow.',
            ],
            [
                'name' => 'TAR UMT Business Case Challenge Clinic',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Seminar Room B',
                'event_description' => 'A guided clinic for students preparing business case analyses, presentations, and strategy recommendations.',
                'posting_description' => 'Improve your case analysis and presentation strategy with focused feedback from facilitators.',
            ],
            [
                'name' => 'TAR UMT Applied Science Innovation Day',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Science Block Foyer',
                'event_description' => 'Student innovation booths and mini demonstrations featuring applied science projects and practical prototypes.',
                'posting_description' => 'Explore applied science prototypes and connect with project teams during this innovation showcase.',
            ],
            [
                'name' => 'TAR UMT Built Environment Site Planning Talk',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Architecture Studio',
                'event_description' => 'A sharing session on site planning fundamentals, space usage, and presentation techniques for studio work.',
                'posting_description' => 'Learn key site planning fundamentals and visual presentation tips for built environment students.',
            ],
            [
                'name' => 'TAR UMT Communication Skills Bootcamp',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Creative Studio',
                'event_description' => 'Interactive communication activities focused on public speaking, storytelling structure, and audience engagement.',
                'posting_description' => 'Build confidence in speaking and storytelling through hands-on communication exercises.',
            ],
            [
                'name' => 'TAR UMT Social Science Leadership Forum',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Lecture Theatre 2',
                'event_description' => 'A panel and discussion forum exploring student leadership, community impact, and project execution.',
                'posting_description' => 'Hear leadership insights and discuss practical community project strategies with student leaders.',
            ],
            [
                'name' => 'TAR UMT Campus Wellness and Study Balance Day',
                'venue' => 'TAR UMT Kuala Lumpur Main Campus - Student Commons',
                'event_description' => 'A wellness-focused event covering study planning, stress management, and healthy student routines.',
                'posting_description' => 'Take part in wellness activities and learn practical study-balance habits for semester success.',
            ],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $club = User::query()
                ->where('email', 'club' . $i . '@seed.test')
                ->where('role', 'club')
                ->first();

            if (! $club) {
                $this->command?->warn('Skipped club' . $i . '@seed.test (account not found).');
                continue;
            }

            $template = $eventTemplates[$i - 1];
            $slug = Str::slug($template['name']) . '-club-' . $i;
            $logoPath = 'seed/event-logos/' . $slug . '.svg';
            $postingImagePath = 'seed/posting-images/' . $slug . '.svg';
            $theme = $themes[$i - 1];

            Storage::disk('public')->put(
                $logoPath,
                $this->svgEventBanner($template['name'], $club->name, $theme, $i)
            );
            Storage::disk('public')->put(
                $postingImagePath,
                $this->svgPostingBanner($template['posting_description'], $template['name'], $club->name, $theme, $i)
            );

            $startDate = $now->copy()->addDays($i)->toDateString();
            $endDate = $now->copy()->addDays($i + 1)->toDateString();

            $event = Event::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'name' => $template['name'],
                ],
                [
                    'description' => $template['event_description'],
                    'venue' => $template['venue'],
                    'status' => 'in_progress',
                    'approval_status' => 'approved',
                    'registration_type' => 'register',
                    'participant_limit' => 10,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'logo_path' => $logoPath,
                ]
            );

            $posting = Posting::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'event_id' => $event->id,
                ],
                [
                    'description' => $template['posting_description'],
                    'status' => 'none',
                    'poster_path' => $postingImagePath,
                    'outdated_at' => null,
                ]
            );

            $posting->images()->updateOrCreate(
                [
                    'position' => 0,
                ],
                [
                    'image_path' => $postingImagePath,
                ]
            );

            $this->command?->info('Seeded event + posting for ' . $club->email . '.');
        }
    }

    private function svgEventBanner(string $title, string $clubName, array $theme, int $variant): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeClubName = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');
        $decor = $this->svgDecoration($variant, $theme['accent']);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="628" viewBox="0 0 1200 628">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$theme['from']}"/>
      <stop offset="100%" stop-color="{$theme['to']}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="628" fill="url(#bg)"/>
  {$decor}
  <text x="60" y="128" fill="#FFFFFF" font-size="42" font-family="Arial, sans-serif" font-weight="700">TAR UMT Event</text>
  <text x="60" y="200" fill="#EAF4FF" font-size="28" font-family="Arial, sans-serif">{$safeClubName}</text>
  <text x="60" y="280" fill="#FFFFFF" font-size="44" font-family="Arial, sans-serif" font-weight="700">{$safeTitle}</text>
  <text x="60" y="560" fill="#D7E9FF" font-size="24" font-family="Arial, sans-serif">Community Platform Seed Data</text>
</svg>
SVG;
    }

    private function svgPostingBanner(string $description, string $eventName, string $clubName, array $theme, int $variant): string
    {
        $safeDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $safeEventName = htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8');
        $safeClubName = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');
        $decor = $this->svgDecoration($variant + 3, $theme['accent']);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="628" viewBox="0 0 1200 628">
  <defs>
    <linearGradient id="bg2" x1="1" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="{$theme['to']}"/>
      <stop offset="100%" stop-color="{$theme['from']}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="628" fill="url(#bg2)"/>
  {$decor}
  <rect x="58" y="66" width="1084" height="496" rx="26" fill="#FFFFFF" fill-opacity="0.10"/>
  <text x="90" y="142" fill="#FFFFFF" font-size="38" font-family="Arial, sans-serif" font-weight="700">Event Posting</text>
  <text x="90" y="198" fill="#EAF4FF" font-size="26" font-family="Arial, sans-serif">{$safeClubName}</text>
  <text x="90" y="274" fill="#FFFFFF" font-size="40" font-family="Arial, sans-serif" font-weight="700">{$safeEventName}</text>
  <text x="90" y="354" fill="#EAF4FF" font-size="25" font-family="Arial, sans-serif">{$safeDescription}</text>
  <text x="90" y="526" fill="#D7E9FF" font-size="22" font-family="Arial, sans-serif">Join now via TAR UMT Community Platform</text>
</svg>
SVG;
    }

    private function svgDecoration(int $variant, string $accent): string
    {
        return match ($variant % 4) {
            0 => <<<SVG
<circle cx="1030" cy="112" r="84" fill="{$accent}" fill-opacity="0.35"/>
<circle cx="1110" cy="190" r="44" fill="{$accent}" fill-opacity="0.25"/>
<circle cx="980" cy="228" r="30" fill="{$accent}" fill-opacity="0.22"/>
SVG,
            1 => <<<SVG
<rect x="910" y="74" width="220" height="24" rx="12" fill="{$accent}" fill-opacity="0.35"/>
<rect x="860" y="124" width="290" height="24" rx="12" fill="{$accent}" fill-opacity="0.28"/>
<rect x="940" y="174" width="210" height="24" rx="12" fill="{$accent}" fill-opacity="0.21"/>
SVG,
            2 => <<<SVG
<path d="M780 88 C920 220, 1020 0, 1180 132 L1180 250 L780 250 Z" fill="{$accent}" fill-opacity="0.24"/>
<path d="M780 232 C920 362, 1040 132, 1180 262 L1180 362 L780 362 Z" fill="{$accent}" fill-opacity="0.16"/>
SVG,
            default => <<<SVG
<polygon points="980,50 1130,50 1055,176" fill="{$accent}" fill-opacity="0.26"/>
<polygon points="900,180 1010,180 955,272" fill="{$accent}" fill-opacity="0.20"/>
<polygon points="1030,220 1160,220 1095,328" fill="{$accent}" fill-opacity="0.18"/>
SVG,
        };
    }
}
