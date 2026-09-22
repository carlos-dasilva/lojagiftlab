<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['groups' => config('store.groups'), 'values' => Setting::allValues()]);
    }

    public function update(Request $r)
    {
        $rules = [];
        foreach (config('store.groups') as [$label, $fields]) {
            foreach ($fields as $key => [$fieldLabel, $type]) {
                $rules[$key] = match ($type) {
                    'file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                    'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                    'url' => 'nullable|url:http,https|max:2000',
                    'email' => 'required|email|max:150',
                    'checkbox' => 'nullable|boolean',
                    default => 'nullable|string|max:2000',
                };
            }
        }
        $rules['site_name'] = 'required|string|max:80';
        $rules['hero_title'] = 'required|string|max:180';
        $data = $r->validate($rules);
        foreach (config('store.groups') as $group => [$label, $fields]) {
            foreach ($fields as $key => [$fieldLabel, $type]) {
                if ($type === 'file') {
                    if ($r->hasFile($key)) {
                        $path = app(ImageService::class)->single($r->file($key), 'brand');
                        $old = Setting::value($key);
                        try {
                            Setting::put($key, $path, $group);
                        } catch (\Throwable $error) {
                            Storage::disk('public')->delete($path);
                            throw $error;
                        }
                        if ($old) {
                            Storage::disk('public')->delete($old);
                        }
                    }
                } elseif (array_key_exists($key, $data)) {
                    Setting::put($key, $type === 'checkbox' ? (string) (int) $r->boolean($key) : ($data[$key] ?? ''), $group);
                }
            }
        }

        return back()->with('success', 'Configurações salvas.');
    }
}
