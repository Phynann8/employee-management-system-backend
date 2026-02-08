<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function myLeaves() { return response()->json([]); }
    public function requestLeave() { return response()->json(['message' => 'Leave requested']); }
}
