<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|max:255|unique:site_settings,key',
            'value' => 'nullable',
            'type' => 'required|in:text,textarea,image',
        ]);

        SiteSetting::create($data);

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting created successfully.');
    }

    public function edit(SiteSetting $siteSetting)
    {
        return view('admin.site-settings.form', ['setting' => $siteSetting]);
    }

    public function update(Request $request, SiteSetting $siteSetting)
    {
        $data = $request->validate([
            'key' => 'required|max:255|unique:site_settings,key,' . $siteSetting->id,
            'value' => 'nullable',
            'type' => 'required|in:text,textarea,image',
        ]);

        $siteSetting->update($data);

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    public function destroy(SiteSetting $siteSetting)
    {
        $siteSetting->delete();
        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Setting deleted successfully.');
    }
}
