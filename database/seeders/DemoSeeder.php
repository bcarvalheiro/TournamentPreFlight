<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The prototype's world (design/prototype/app.js): invented shops, ~6 weeks of
 * events with realistic going counts, and the demo accounts. Dates are
 * relative to today, so the data always looks current.
 */
class DemoSeeder extends Seeder
{
    /**
     * @var array<string, array{string, string, string}> key => [name, city, district]
     */
    private const SHOPS = [
        'dado' => ['Dado Dourado', 'Lisboa', 'Lisboa'],
        'cartaalta' => ['Carta Alta', 'Porto', 'Porto'],
        'toca' => ['Toca do Tabuleiro', 'Braga', 'Braga'],
        'meeples' => ['Casa dos Meeples', 'Coimbra', 'Coimbra'],
        'farol' => ['Farol Geek', 'Faro', 'Faro'],
        'ria' => ['Ria Cartas & Jogos', 'Aveiro', 'Aveiro'],
        'sado' => ['Sado Games', 'Setúbal', 'Setúbal'],
        'levada' => ['Levada TCG', 'Funchal', 'Madeira'],
        'liz' => ['Liz Card Club', 'Leiria', 'Leiria'],
    ];

    /**
     * Anonymous players behind the counts. At least as many as the two biggest
     * same-day events together (38 + 8 on day 4), so they never overlap.
     */
    private const CROWD_SIZE = 60;

    /**
     * [day offset, Lisbon start time or 'now', type, shop, going]. From the
     * prototype; its League and Friendly rows are Casual, plus one Prerelease.
     *
     * @var list<array{int, string, EventType, string, int}>
     */
    private const EVENTS = [
        [-2, '15:00', EventType::Challenge, 'dado', 14],
        [0, 'now', EventType::Casual, 'cartaalta', 9],
        [1, '18:30', EventType::Casual, 'dado', 6],
        [2, '19:00', EventType::Casual, 'ria', 4],
        [3, '10:00', EventType::Cup, 'toca', 27],
        [3, '14:00', EventType::Challenge, 'sado', 11],
        [4, '10:00', EventType::Cup, 'dado', 38],
        [4, '15:00', EventType::Challenge, 'meeples', 8],
        [6, '18:30', EventType::Casual, 'toca', 5],
        [7, '19:00', EventType::Casual, 'liz', 3],
        [9, '18:00', EventType::Casual, 'cartaalta', 12],
        [10, '10:00', EventType::Cup, 'cartaalta', 31],
        [10, '14:30', EventType::Challenge, 'farol', 7],
        [11, '10:00', EventType::Challenge, 'levada', 6],
        [11, '14:00', EventType::Cup, 'meeples', 16],
        [13, '18:30', EventType::Casual, 'dado', 7],
        [14, '14:00', EventType::Prerelease, 'liz', 6],
        [16, '19:00', EventType::Casual, 'sado', 5],
        [17, '10:00', EventType::Cup, 'farol', 13],
        [17, '14:00', EventType::Challenge, 'ria', 9],
        [18, '10:00', EventType::Cup, 'liz', 10],
        [20, '18:30', EventType::Casual, 'meeples', 4],
        [24, '10:00', EventType::Challenge, 'toca', 12],
        [24, '14:00', EventType::Casual, 'dado', 9],
        [25, '10:00', EventType::Cup, 'sado', 19],
        [31, '10:00', EventType::Cup, 'toca', 8],
        [31, '14:00', EventType::Challenge, 'cartaalta', 3],
        [32, '10:00', EventType::Challenge, 'dado', 2],
        [38, '10:00', EventType::Cup, 'ria', 5],
        [39, '14:00', EventType::Challenge, 'farol', 1],
    ];

    /**
     * Seed the demo data.
     */
    public function run(): void
    {
        // The demo admin has a known password: never seed this on a real server.
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoSeeder skipped: it only runs in local and testing.');

            return;
        }

        $shops = collect(self::SHOPS)->map(fn (array $shop): Shop => Shop::factory()->create([
            'name' => $shop[0],
            'city' => $shop[1],
            'district' => $shop[2],
            'official_name_key' => mb_strtolower("{$shop[0]}|{$shop[1]}"),
        ]));

        $crowd = PlayerProfile::factory()->count(self::CROWD_SIZE)->create();
        $crowdTwice = $crowd->concat($crowd);
        $next = 0;

        $events = collect(self::EVENTS)->map(function (array $row) use ($shops, $crowdTwice, &$next): Event {
            [$day, $time, $type, $shop, $going] = $row;

            $event = Event::factory()->for($shops[$shop])->create([
                'type' => $type,
                'starts_at' => $this->startsAt($day, $time),
            ]);

            // Each event takes the next block of the crowd (wrapping around, hence
            // the crowd twice), so nobody goes everywhere and same-day events,
            // which sit next to each other in EVENTS, never share a player.
            $this->attend($event, $crowdTwice->slice($next, $going));
            $next = ($next + $going) % self::CROWD_SIZE;

            return $event;
        });

        $this->seedDemoAccounts($events);
    }

    /**
     * Solo player, parent with two children, and an admin. Password "password"
     * (the kit's) until M06 removes passwords.
     *
     * @param  Collection<int, Event>  $events
     */
    private function seedDemoAccounts(Collection $events): void
    {
        $solo = User::factory()->create(['name' => 'Solo Demo', 'email' => 'solo@preflight.test']);
        $soloProfile = PlayerProfile::factory()->for($solo)->create(['name' => 'Solo Demo', 'birth_year' => 1995]);
        $this->attend($events[4], [$soloProfile]);
        $this->attend($events[11], [$soloProfile]);

        $family = User::factory()->create(['name' => 'Family Demo', 'email' => 'family@preflight.test']);
        $parent = PlayerProfile::factory()->for($family)->create(['name' => 'Parent Demo', 'birth_year' => 1985]);
        $thisYear = now('Europe/Lisbon')->year;
        $junior = PlayerProfile::factory()->child()->for($family)->create(['name' => 'Junior Demo', 'birth_year' => $thisYear - 11]);
        $senior = PlayerProfile::factory()->child()->for($family)->create(['name' => 'Senior Demo', 'birth_year' => $thisYear - 12]);
        $this->attend($events[6], [$parent, $junior, $senior]);

        User::factory()->create(['name' => 'Admin Demo', 'email' => 'admin@preflight.test', 'is_admin' => true]);
    }

    /**
     * The start in UTC for a Lisbon wall-clock time `$day` days from today.
     * "now" means an hour ago, so a 2-hour casual is ongoing.
     */
    private function startsAt(int $day, string $time): CarbonInterface
    {
        if ($time === 'now') {
            return now()->subHour()->startOfMinute();
        }

        return Carbon::parse($time, 'Europe/Lisbon')->addDays($day)->utc();
    }

    /**
     * @param  iterable<PlayerProfile>  $profiles
     */
    private function attend(Event $event, iterable $profiles): void
    {
        foreach ($profiles as $profile) {
            Attendance::factory()->for($event)->for($profile)->create();
        }
    }
}
