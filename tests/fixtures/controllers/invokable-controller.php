<?php

namespace App\Http\Controllers;

use App\Events\ReportGenerated;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        event(new ReportGenerated());

        return view('report');
    }
}
