<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoadCareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function citizen(): User
    {
        return User::factory()->create(['city' => 'Erbil']);
    }

    private function government(): User
    {
        $user = $this->citizen();
        $user->forceFill(['role' => 'government'])->save();
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Deep pothole near the university', 'description' => 'There is a deep pothole in the right lane near the entrance.',
            'category' => 'pothole', 'severity' => 'severe', 'city' => 'Erbil', 'address' => 'University road, main entrance',
            'latitude' => 36.1911, 'longitude' => 44.0092, 'photo' => UploadedFile::fake()->image('road.jpg', 800, 600),
        ], $overrides);
    }

    private function report(User $user, array $overrides = []): Report
    {
        $data = $this->payload(); unset($data['photo']);
        return $user->reports()->create(array_replace($data, ['reference' => 'RC-TEST-'.uniqid()], $overrides));
    }

    public function test_registration_never_grants_government_role(): void
    {
        $this->post('/register', ['name' => 'New Citizen', 'email' => 'new@example.com', 'password' => 'StrongPassword123!', 'password_confirmation' => 'StrongPassword123!', 'city' => 'Erbil', 'role' => 'government'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'citizen']);
        $this->get('/government')->assertForbidden();
    }

    public function test_both_demo_accounts_login_and_passwords_are_hashed(): void
    {
        $this->seed();
        $this->post('/login', ['email' => 'citizen@demo.com', 'password' => 'RoadCare123!'])->assertRedirect('/dashboard');
        $this->assertNotEquals('RoadCare123!', User::where('email', 'citizen@demo.com')->first()->password);
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email' => 'government@roadcare.test', 'password' => 'RoadCare123!'])->assertRedirect('/government');
    }

    public function test_login_is_throttled_and_wrong_passwords_fail(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
            $response->assertSessionHasErrors('email');
        }
        $this->assertGuest();
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }

    public function test_guests_cannot_submit_or_view_reports(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->post('/reports', $this->payload())->assertRedirect('/login');
    }

    public function test_citizen_can_upload_a_private_photo_and_receive_a_reference(): void
    {
        $user = $this->citizen();
        $this->actingAs($user)->post('/reports', $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $report = Report::firstOrFail();
        $this->assertStringStartsWith('RC-', $report->reference);
        $this->assertEquals('submitted', $report->status);
        $this->assertEquals($user->id, $report->user_id);
        Storage::disk('local')->assertExists($report->photo_path);
        $this->assertDatabaseHas('report_updates', ['report_id' => $report->id, 'status' => 'submitted']);
        $this->get('/reports/'.$report->id.'/photo')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/reports/'.$report->id)->assertInertia(fn (Assert $page) => $page->component('Reports/Show')->where('report.reference', $report->reference)->missing('report.photo_path'));
    }

    public function test_only_kurdistan_service_area_coordinates_are_accepted(): void
    {
        foreach ([[36.1911, 44.0092], [35.557, 45.435], [36.867, 42.95], [35.177, 45.986]] as [$lat, $lng]) {
            $this->assertTrue(ServiceArea::contains($lat, $lng));
        }
        foreach ([[33.3152, 44.3661], [36.34, 43.13], [36.3, 47.0], [0, 0]] as [$lat, $lng]) {
            $this->assertFalse(ServiceArea::contains($lat, $lng));
        }
        $this->actingAs($this->citizen())->post('/reports', $this->payload(['latitude' => 33.3152, 'longitude' => 44.3661]))->assertSessionHasErrors('latitude');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_invalid_and_missing_photos_are_rejected(): void
    {
        $this->actingAs($this->citizen());
        $this->post('/reports', $this->payload(['photo' => null]))->assertSessionHasErrors('photo');
        $this->post('/reports', $this->payload(['photo' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')]))->assertSessionHasErrors('photo');
        $this->post('/reports', $this->payload(['photo' => UploadedFile::fake()->create('large.jpg', 9000, 'image/jpeg')]))->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_corrupted_image_returns_validation_error_instead_of_a_server_error(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jr1sAAAAASUVORK5CYII=');
        $photo = UploadedFile::fake()->createWithContent('corrupt.png', $png);
        $this->actingAs($this->citizen())->post('/reports', $this->payload(['photo' => $photo]))->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_citizens_cannot_access_other_citizens_reports_photos_or_government_actions(): void
    {
        $report = $this->report($this->citizen());
        $this->actingAs($this->citizen());
        $this->get('/reports/'.$report->id)->assertForbidden();
        $this->get('/reports/'.$report->id.'/photo')->assertForbidden();
        $this->patch('/government/reports/'.$report->id, ['status' => 'resolved'])->assertForbidden();
        $this->get('/government/reports')->assertForbidden();
    }

    public function test_citizen_list_and_statistics_are_scoped_to_their_own_reports(): void
    {
        $mine = $this->citizen();
        $own = $this->report($mine);
        $this->report($this->citizen());
        $this->actingAs($mine)->get('/reports')->assertInertia(fn (Assert $page) => $page->component('Reports/Index')->has('reports.data', 1)->where('reports.data.0.id', $own->id));
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->component('Dashboard')->where('stats.total', 1));
    }

    public function test_government_can_manage_all_reports_and_filter_them(): void
    {
        $this->report($this->citizen(), ['city' => 'Erbil']);
        $this->report($this->citizen(), ['city' => 'Duhok', 'status' => 'reviewing']);
        $this->actingAs($this->government())->get('/government/reports?city=Duhok&status=reviewing')->assertInertia(fn (Assert $page) => $page->component('Reports/Index')->has('reports.data', 1)->where('reports.data.0.city', 'Duhok'));
    }

    public function test_government_workflow_is_recorded_and_visible_to_the_citizen(): void
    {
        $citizen = $this->citizen(); $report = $this->report($citizen);
        $this->actingAs($this->government());
        $current = 'submitted';
        foreach (['reviewing', 'scheduled', 'in_progress', 'resolved'] as $status) {
            $this->patch('/government/reports/'.$report->id, ['status' => $status, 'current_status' => $current, 'department' => 'Erbil Maintenance', 'note' => 'The maintenance team updated this report.'])->assertSessionHasNoErrors();
            $current = $status;
        }
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'resolved', 'department' => 'Erbil Maintenance']);
        $this->assertNotNull($report->fresh()->resolved_at);
        $this->assertDatabaseCount('report_updates', 4);
        $this->actingAs($citizen)->get('/reports/'.$report->id)->assertInertia(fn (Assert $page) => $page->component('Reports/Show')->where('report.status', 'resolved')->has('report.updates', 4));
    }

    public function test_invalid_transitions_and_stale_statuses_are_rejected(): void
    {
        $report = $this->report($this->citizen());
        $this->actingAs($this->government());
        $base = ['current_status' => 'submitted', 'department' => null, 'note' => 'A valid explanation for the citizen.'];
        $this->patch('/government/reports/'.$report->id, $base + ['status' => 'resolved'])->assertSessionHasErrors('status');
        $this->patch('/government/reports/'.$report->id, array_replace($base, ['current_status' => 'reviewing', 'status' => 'scheduled']))->assertSessionHasErrors('status');
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'submitted']);
        $this->assertDatabaseCount('report_updates', 0);
    }

    public function test_regional_map_hides_unreviewed_reports_and_private_information(): void
    {
        $owner = $this->citizen();
        $this->report($owner, ['status' => 'submitted']);
        $this->report($owner, ['status' => 'reviewing']);
        $this->actingAs($this->citizen())->get('/map')->assertInertia(fn (Assert $page) => $page->component('Map')->has('mapReports', 1)->missing('mapReports.0.user_id')->missing('mapReports.0.description')->missing('mapReports.0.photo_path'));
    }

    public function test_sorani_language_is_persisted_and_validation_is_translated(): void
    {
        $this->from('/login')->post('/language', ['locale' => 'ckb'])->assertRedirect('/login');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('locale', 'ckb'));
        $this->post('/login', ['email' => 'wrong@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertEquals('ئیمەیڵ یان وشەی نهێنی هەڵەیە.', session('errors')->first('email'));
    }
}
