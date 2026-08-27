<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $services = $query->orderBy('sort_order')->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        if ($request->header('X-LiveSearch') === '1') {
            return view('admin.services._table', compact('services'));
        }

        $servicesData = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'icon' => $service->icon,
                'description' => $service->description,
                'category' => $service->category,
                'is_active' => (bool) $service->is_active,
                'sort_order' => $service->sort_order,
            ];
        })->values();

        return view('admin.services.index', compact('services', 'servicesData'));
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $service = Service::create($data);

        $logger->record('service.created', $service, "Service {$service->name} created.", [
            'category' => $service->category,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(Request $request, Service $service, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $service->update($data);

        $logger->record('service.updated', $service, "Service {$service->name} updated.", [
            'category' => $service->category,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service, ActivityLogger $logger): RedirectResponse
    {
        $service->delete();

        $logger->record('service.deleted', $service, "Service {$service->name} deleted.");

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'icon' => 'nullable|max:50',
            'category' => 'nullable|in:Amenities,Dining,Activities,Events',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
