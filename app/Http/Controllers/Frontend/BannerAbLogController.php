<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerAbLogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'variant' => 'required|string|max:1',
            'event'   => 'required|string|max:20',
            'listing_id' => 'nullable|string|max:50',
        ]);

        DB::table('banner_ab_logs')->insert([
            'variant'    => $request->input('variant'),
            'event'      => $request->input('event'),
            'listing_id' => $request->input('listing_id'),
            'session_id' => $request->session()->getId()
                ? hash('sha256', $request->session()->getId())
                : null,
            'ip'         => hash('sha256', $request->ip()),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
