<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserListingController extends Controller
{
    public function index() { return redirect('/'); }
    public function create($building_id) { return redirect('/'); }
    public function store(Request $request) { return redirect('/'); }
    public function step($step) { return redirect('/'); }
    public function stepSave(Request $request) { return redirect('/'); }
    public function edit($id) { return redirect('/'); }
    public function update(Request $request, $id) { return redirect('/'); }
    public function publish($id) { return redirect('/'); }
    public function destroy($id) { return redirect('/'); }
    public function requestPublish($id) { return redirect('/'); }
    public function verifyPublish($id) { return redirect('/'); }
    public function toggleActive($id) { return redirect('/'); }
}
