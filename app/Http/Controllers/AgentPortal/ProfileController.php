<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $agent    = Auth::guard('agent')->user()->load('settings');
        $settings = $agent->settings;

        return view('agent-portal.profile', compact('agent', 'settings'));
    }

    public function update(Request $request)
    {
        $agent = Auth::guard('agent')->user();

        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'brokerage'       => 'nullable|string|max:120',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'required|email|max:120',
            'bio'             => 'nullable|string|max:3000',
            'intro_video_url' => 'nullable|url|max:300',
        ]);

        $agent->fill([
            'name'      => $validated['name'],
            'brokerage' => $validated['brokerage'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'email'     => $validated['email'],
            'bio'       => $validated['bio'] ?? null,
        ]);

        if ($request->hasFile('photo')) {
            $request->validate(['photo' => 'image|max:4096']);
            $dir  = "agents/{$agent->id}";
            Storage::disk('public')->deleteDirectory($dir);
            $path = $request->file('photo')->store($dir, 'public');
            $agent->photo_path = $path;
        }

        $agent->save();

        $socialLinks = $request->only([
            'instagram', 'facebook', 'linkedin', 'youtube', 'tiktok', 'twitter',
        ]);

        $settings = $agent->settings()->firstOrCreate(['agent_id' => $agent->id]);
        $settings->update([
            'intro_video_url' => $validated['intro_video_url'] ?? null,
            'social_links'    => array_filter($socialLinks, fn($v) => !empty($v)),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }
}
