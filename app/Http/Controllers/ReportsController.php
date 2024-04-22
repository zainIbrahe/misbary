<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\User;

class ReportsController extends Controller
{
    public function index()
    {
        $userCount = User::count(); // Retrieve user count

        return view('/vendor/voyager/reports.report', compact('userCount'));
    }
}
