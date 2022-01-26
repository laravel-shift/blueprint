<?php

namespace App\Http\Controllers;

use App\Events\ReportGenerated;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        event(new ReportGenerated());

        return view('report');
    }
}
