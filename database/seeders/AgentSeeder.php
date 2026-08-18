<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Models\AgentTerritory;
use App\Models\AgentMlsId;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    /**
     * Idempotent seeder — safe to re-run.
     */
    public function run(): void
    {
        $bio = "Randy Dyck has been helping buyers and sellers navigate the South Surrey, White Rock, and Cloverdale markets for over two decades. A consistent RE/MAX award winner, Randy is known for his deep neighbourhood knowledge, straightforward advice, and relentless follow-through.\n\nWhether you're upsizing into a family home in South Surrey, purchasing your first condo in Cloverdale, or selling a long-held White Rock property, Randy brings real data and real candour to every conversation. He's not interested in telling you what you want to hear — he's interested in getting you the best outcome.\n\nRandy lives in the communities he serves and raises his family here. That's not a tagline — it's the reason he cares about every transaction.";

        $agent = Agent::firstOrCreate(
            ['slug' => 'randy'],
            [
                'name'           => 'Randy Dyck',
                'brokerage'      => 'RE/MAX Crest Realty',
                'phone'          => '604-807-4366',
                'email'          => null,
                'bio'            => $bio,
                'photo_path'     => 'frontend/images/teamagents/randy_dyck.jpg',
                'logo_path'      => null,
                'license_number' => 'R4568290',
                'theme_slug'     => 'classic-dark',
                'theme_color'    => '#c9a96e',
                'status'         => 'active',
                'password'       => Hash::make('changeme123'),
            ]
        );

        $agent->fill([
            'bio'            => $bio,
            'photo_path'     => 'frontend/images/teamagents/randy_dyck.jpg',
            'license_number' => 'R4568290',
        ])->save();

        AgentSettings::firstOrCreate(
            ['agent_id' => $agent->id],
            [
                'custom_domain'       => 'randydyck.com',
                'notification_email'  => null,
                'notification_phone'  => null,
                'featured_listing_ids'=> null,
                'social_links'        => null,
                'ga4_id'              => null,
                'fb_pixel_id'         => null,
                'fub_enabled'         => false,
                'fub_api_key'         => null,
                'lead_routing'        => null,
                'intro_video_url'     => null,
            ]
        );

        AgentMlsId::firstOrCreate(
            ['agent_id' => $agent->id, 'mls_id' => 'FDYCKRA']
        );

        $territories = [
            ['city' => 'Surrey',       'subarea' => 'South Surrey White Rock', 'board' => null],
            ['city' => 'Surrey',       'subarea' => 'Cloverdale',              'board' => null],
            ['city' => 'White Rock',   'subarea' => null,                      'board' => null],
        ];

        foreach ($territories as $territory) {
            AgentTerritory::firstOrCreate(
                [
                    'agent_id' => $agent->id,
                    'city'     => $territory['city'],
                    'subarea'  => $territory['subarea'],
                ],
                ['board' => $territory['board']]
            );
        }

        $this->command->info("AgentSeeder: Randy Dyck (slug=randy, id={$agent->id}) seeded.");
    }
}
