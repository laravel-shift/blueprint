<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function show(Request $request, Customer $customer): Response
    {
        $customers = Customer::all();

        return Inertia::render('customer.show', [
            'customer' => $customer,
            'customers' => $customers,
        ]);
    }
}
