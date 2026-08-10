<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteSettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = SiteSetting::orderBy('key')->paginate(20);

        $settingsData = $settings->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
            ];
        })->values();

        return view('admin.site-settings.index', compact('settings', 'settingsData'));
    }

    public function create()
    {
        return view('admin.site-settings.form', ['setting' => new SiteSetting]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $data = $request->validate([
            'key' => 'required|max:255|unique:site_settings,key',
            'value' => [
                'nullable',
                Rule::when(
                    fn ($input) => str_ends_with((string) ($input['key'] ?? ''), '_url') && filled($input['value'] ?? null),
                    ['url', 'regex:/^https?:\/\//i']
                ),
            ],
            'type' => 'required|in:text,textarea,image',
        ]);

        $setting = SiteSetting::create($data);

        $logger->record('setting.created', $setting, "Site setting {$setting->key} created.", [
            'type' => $setting->type,
        ]);

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting created successfully.');
    }

    public function edit(SiteSetting $siteSetting)
    {
        return view('admin.site-settings.form', ['setting' => $siteSetting]);
    }

    public function update(Request $request, SiteSetting $siteSetting, ActivityLogger $logger)
    {
        $data = $request->validate([
            'key' => 'required|max:255|unique:site_settings,key,'.$siteSetting->id,
            'value' => [
                'nullable',
                Rule::when(
                    fn ($input) => str_ends_with((string) ($input['key'] ?? ''), '_url') && filled($input['value'] ?? null),
                    ['url', 'regex:/^https?:\/\//i']
                ),
            ],
            'type' => 'required|in:text,textarea,image',
        ]);

        $siteSetting->update($data);

        $logger->record('setting.updated', $siteSetting, "Site setting {$siteSetting->key} updated.", [
            'type' => $siteSetting->type,
        ]);

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    public function destroy(SiteSetting $siteSetting, ActivityLogger $logger)
    {
        $siteSetting->delete();

        $logger->record('setting.deleted', $siteSetting, "Site setting {$siteSetting->key} deleted.");

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting deleted successfully.');
    }
}
