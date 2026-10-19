<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Known demo passwords and sample reports must never be seeded in production.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo seeding is disabled outside local/testing.');
            return;
        }
        $citizen = User::firstOrCreate(['email' => 'citizen@demo.com'], ['name' => 'Dara Ahmed', 'password' => 'RoadCare123!', 'city' => 'Erbil']);
        $official = User::firstOrCreate(['email' => 'government@roadcare.test'], ['name' => 'Road Operations', 'password' => 'RoadCare123!', 'city' => 'Erbil']);
        $official->forceFill(['role' => 'government'])->save();
        $samples = [
            ['Deep pothole on the university road', 'A deep pothole is affecting the right lane near the university entrance.', 'pothole', 'critical', 'Erbil', 'University road, near the main entrance', 36.1911, 44.0092, 'reviewing'],
            ['Cracked surface near the city park', 'Several cracks are spreading across the road surface close to the park.', 'road_crack', 'moderate', 'Sulaymaniyah', 'Salim Street, near the park', 35.557, 45.435, 'scheduled'],
            ['Drainage cover needs repair', 'The drainage cover is loose and creates a risk for vehicles and pedestrians.', 'drainage', 'severe', 'Duhok', 'City center, main road', 36.867, 42.95, 'in_progress'],
            ['Damaged speed bump at the junction', 'The speed bump is broken and has sharp edges along the center of the road.', 'speed_bump', 'moderate', 'Halabja', 'Main junction, city center', 35.177, 45.986, 'submitted'],
            ['Uneven road surface near the market', 'The uneven road surface near the market entrance has been repaired.', 'surface_damage', 'minor', 'Erbil', 'Market road, east entrance', 36.202, 44.019, 'resolved'],
            ['Pothole in the outer lane', 'A pothole in the outer lane is difficult for drivers to see at night.', 'pothole', 'severe', 'Sulaymaniyah', 'Outer ring road', 35.569, 45.414, 'submitted'],
        ];
        foreach ($samples as $index => [$title, $description, $category, $severity, $city, $address, $latitude, $longitude, $status]) {
            if (Report::where('reference', 'RC-DEMO-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT))->exists()) continue;
            $created = now()->subDays(count($samples) - $index);
            $report = Report::create(compact('title', 'description', 'category', 'severity', 'city', 'address', 'latitude', 'longitude', 'status') + [
                'user_id' => $citizen->id, 'reference' => 'RC-DEMO-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'department' => $status === 'submitted' ? null : $city.' Road Maintenance', 'resolved_at' => $status === 'resolved' ? now() : null,
            ]);
            $report->forceFill(['created_at' => $created])->save();
            $report->updates()->create(['user_id' => $citizen->id, 'status' => 'submitted', 'note' => 'report_received', 'created_at' => $created]);
            $trail = ['reviewing' => 'demo_reviewed', 'scheduled' => 'demo_scheduled', 'in_progress' => 'demo_repair', 'resolved' => 'demo_resolved'];
            foreach ($trail as $step => $note) {
                if ($status === 'submitted') break;
                $report->updates()->create(['user_id' => $official->id, 'status' => $step, 'note' => $note]);
                if ($step === $status) break;
            }
        }
    }
}
