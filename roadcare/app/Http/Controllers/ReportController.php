<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Support\ServiceArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $government = $request->user()->role === 'government';
        $query = Report::query()->when(! $government, fn ($query) => $query->where('user_id', $request->user()->id));
        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'critical' => (clone $query)->where('severity', 'critical')->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentReports' => (clone $query)->latest()->limit(5)->get()->map(fn ($report) => $this->summary($report)),
            'mapReports' => $this->mapReports(),
            'cityStats' => Report::selectRaw('city, count(*) as total, sum(case when status = ? then 1 else 0 end) as resolved', ['resolved'])->groupBy('city')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $government = $request->user()->role === 'government';
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(config('roadcare.statuses'))],
            'city' => ['nullable', Rule::in(config('roadcare.cities'))],
            'severity' => ['nullable', Rule::in(config('roadcare.severities'))],
        ]);
        $query = Report::query()->when(! $government, fn ($query) => $query->where('user_id', $request->user()->id));
        foreach (['status', 'city', 'severity'] as $filter) {
            $query->when($filters[$filter] ?? null, fn ($query, $value) => $query->where($filter, $value));
        }
        $query->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%')));
        return Inertia::render('Reports/Index', [
            'reports' => $query->latest()->paginate(12)->withQueryString()->through(fn ($report) => $this->summary($report)),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->role === 'citizen', 403);
        return Inertia::render('Reports/Create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'citizen', 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:140'],
            'description' => ['required', 'string', 'min:15', 'max:3000'],
            'category' => ['required', Rule::in(config('roadcare.categories'))],
            'severity' => ['required', Rule::in(config('roadcare.severities'))],
            'city' => ['required', Rule::in(config('roadcare.cities'))],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        if (! ServiceArea::contains((float) $data['latitude'], (float) $data['longitude'])) {
            throw ValidationException::withMessages(['latitude' => __('roadcare.outside_region')]);
        }
        // Strip GPS/EXIF metadata by storing only decoded image pixels.
        $image = @imagecreatefromstring(file_get_contents($request->file('photo')->getRealPath()));
        if ($image === false) {
            throw ValidationException::withMessages(['photo' => __('roadcare.invalid_photo')]);
        }
        $path = 'reports/'.Str::uuid().'.jpg';
        ob_start();
        imagejpeg($image, null, 88);
        $bytes = ob_get_clean();
        if (! Storage::disk('local')->put($path, $bytes)) {
            abort(500, 'Could not save photo. Please try again.');
        }
        unset($data['photo']);
        try {
            $report = DB::transaction(function () use ($request, $data, $path) {
                $report = $request->user()->reports()->create([...$data, 'photo_path' => $path, 'reference' => 'RC-'.now()->format('ymd').'-'.strtoupper(Str::random(6))]);
                $report->updates()->create(['user_id' => $request->user()->id, 'status' => 'submitted', 'note' => 'report_received']);
                return $report;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        return redirect('/reports/'.$report->id)->with('success', __('roadcare.report_created'));
    }

    public function show(Request $request, Report $report)
    {
        $this->authorizeReport($request, $report);
        $report->load(['updates.user:id,name,role', 'user:id,name,email,phone']);
        $data = [...$this->summary($report), 'description' => $report->description, 'latitude' => $report->latitude, 'longitude' => $report->longitude, 'department' => $report->department, 'photo_url' => $report->photo_path ? '/reports/'.$report->id.'/photo' : null];
        $data['updates'] = $report->updates->map(fn ($update) => [
            'id' => $update->id, 'status' => $update->status, 'note' => $update->note,
            'by' => $update->user->role === 'government' ? 'government' : 'citizen',
            'created_at' => $update->created_at->toIso8601String(),
        ]);
        if ($request->user()->role === 'government') {
            $data['citizen'] = $report->user->only(['name', 'email', 'phone']);
        }
        return Inertia::render('Reports/Show', ['report' => $data, 'transitions' => config('roadcare.transitions.'.$report->status, [])]);
    }

    public function photo(Request $request, Report $report)
    {
        $this->authorizeReport($request, $report);
        abort_unless($report->photo_path && Storage::disk('local')->exists($report->photo_path), 404);
        return response()->file(Storage::disk('local')->path($report->photo_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(config('roadcare.statuses'))],
            'current_status' => ['required', 'string'],
            'note' => ['required', 'string', 'min:5', 'max:2000'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);
        DB::transaction(function () use ($request, $report, $data) {
            $locked = Report::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== $data['current_status']) {
                throw ValidationException::withMessages(['status' => __('roadcare.stale_report')]);
            }
            if ($data['status'] !== $locked->status && ! in_array($data['status'], config('roadcare.transitions.'.$locked->status, []), true)) {
                throw ValidationException::withMessages(['status' => __('roadcare.invalid_transition')]);
            }
            $locked->update(['status' => $data['status'], 'department' => $data['department'] ?? null, 'resolved_at' => $data['status'] === 'resolved' ? ($locked->resolved_at ?? now()) : null]);
            $locked->updates()->create(['user_id' => $request->user()->id, 'status' => $data['status'], 'note' => $data['note']]);
        });
        return back()->with('success', __('roadcare.report_updated'));
    }

    public function map()
    {
        return Inertia::render('Map', ['mapReports' => $this->mapReports()]);
    }

    private function authorizeReport(Request $request, Report $report): void
    {
        abort_unless($request->user()->role === 'government' || $report->user_id === $request->user()->id, 403);
    }

    private function summary(Report $report): array
    {
        return $report->only(['id', 'reference', 'title', 'category', 'severity', 'city', 'address', 'status', 'created_at', 'updated_at']);
    }

    private function mapReports()
    {
        return Report::whereNotIn('status', ['submitted', 'rejected'])->latest()->limit(1000)->get(['id', 'reference', 'category', 'severity', 'city', 'latitude', 'longitude', 'status']);
    }
}
