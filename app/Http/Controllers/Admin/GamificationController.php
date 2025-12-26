<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GamificationController extends Controller
{
    public function index()
    {
        $badges = Badge::withCount('users')->get();

        return Inertia::render('Admin/Gamification/Index', [
            'badges' => $badges
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'badge_name' => 'required|string|max:255',
            'description' => 'required|string',
            'bonus_points' => 'required|integer|min:0',
            'icon_type' => 'required|in:UPLOAD,EMOJI',
            'icon' => 'nullable|required_if:icon_type,UPLOAD|image|max:2048',
            'icon_emoji' => 'nullable|required_if:icon_type,EMOJI|string|max:10',
            'trigger_type' => 'required|string',
            'trigger_value' => 'required|string',
        ]);

        $path = null;
        if ($request->icon_type === 'UPLOAD' && $request->hasFile('icon')) {
            $path = $request->file('icon')->store('badges', 'public');
        }

        Badge::create([
            'badge_name' => $validated['badge_name'],
            'description' => $validated['description'],
            'bonus_points' => $validated['bonus_points'],
            'icon_url' => $path,
            'icon_emoji' => $request->icon_type === 'EMOJI' ? $validated['icon_emoji'] : null,
            'trigger_type' => $validated['trigger_type'],
            'trigger_value' => $validated['trigger_value'],
        ]);

        return back()->with('success', 'Badge created successfully.');
    }

    /**
     * Update Badge.
     * Menggunakan $id (integer) bukan Model Binding agar lebih aman dari kesalahan route.
     */
    public function update(Request $request, $id)
    {
        $badge = Badge::findOrFail($id);

        $validated = $request->validate([
            'badge_name' => 'required|string|max:255',
            'description' => 'required|string',
            'bonus_points' => 'required|integer|min:0',
            'icon_type' => 'required|in:UPLOAD,EMOJI',
            'icon' => 'nullable|image|max:2048',
            'icon_emoji' => 'nullable|required_if:icon_type,EMOJI|string|max:10',
            'trigger_type' => 'required|string',
            'trigger_value' => 'required|string',
        ]);

        $data = [
            'badge_name' => $validated['badge_name'],
            'description' => $validated['description'],
            'bonus_points' => $validated['bonus_points'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_value' => $validated['trigger_value'],
        ];

        // Logika Ganti Icon
        if ($request->icon_type === 'EMOJI') {
            // Jika ganti ke Emoji, hapus file lama
            if ($badge->icon_url) {
                Storage::disk('public')->delete($badge->icon_url);
            }
            $data['icon_url'] = null;
            $data['icon_emoji'] = $validated['icon_emoji'];
        } else {
            // Jika mode UPLOAD
            $data['icon_emoji'] = null;

            if ($request->hasFile('icon')) {
                // Hapus lama, simpan baru
                if ($badge->icon_url) {
                    Storage::disk('public')->delete($badge->icon_url);
                }
                $data['icon_url'] = $request->file('icon')->store('badges', 'public');
            }
        }

        $badge->update($data);

        return back()->with('success', 'Badge updated successfully.');
    }

    public function destroy($id)
    {
        $badge = Badge::findOrFail($id);

        if ($badge->icon_url) {
            Storage::disk('public')->delete($badge->icon_url);
        }

        $badge->delete();
        return back()->with('success', 'Badge deleted.');
    }
}
