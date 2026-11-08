<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AmiraSetting;
use Illuminate\Http\Request;

class SystemPromptController extends Controller
{
    public function edit()
    {
        $setting = AmiraSetting::first();

        if (! $setting) {
            $setting = AmiraSetting::create([
                'system_prompt' => config('amira.system_prompt'),
            ]);
        }

        return view('admin.system_prompt.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'system_prompt' => 'required|string',
        ]);

        $setting = AmiraSetting::firstOrFail();
        $setting->update($data);

        return redirect()
            ->route('admin.system-prompt.edit')
            ->with('success', 'System prompt AMIRA berhasil diperbarui.');
    }
}
