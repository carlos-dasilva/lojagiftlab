<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings');
    }

    public function update(Request $r)
    {
        $data = $r->validate(['site_name' => 'required|max:80', 'site_email' => 'required|email', 'hero_title' => 'required|max:180', 'hero_subtitle' => 'nullable|max:500', 'instagram' => 'nullable|url', 'whatsapp' => 'nullable|max:30', 'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/']);
        foreach ($data as $key => $value) {
            Setting::put($key, $value, str_starts_with($key, 'hero_') ? 'home' : 'identity');
        }

        return back()->with('success', 'Configurações salvas.');
    }
}
