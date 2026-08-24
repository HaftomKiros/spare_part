<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('sales');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('customer_code', 'like', "%{$request->search}%");
            });
        }
        if ($request->type) {
            $query->where('customer_type', $request->type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        return view('sales.customers.index', compact('customers'));
    }

    public function create()
    {
        $code = Customer::generateCode();
        return view('sales.customers.create', compact('code'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'phone'         => 'required|string|max:20',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:300',
            'city'          => 'nullable|string|max:100',
            'customer_type' => 'required|in:individual,business',
            'status'        => 'required|in:active,inactive',
            'notes'         => 'nullable|string|max:500',
        ]);

        $data['customer_code'] = Customer::generateCode();
        Customer::create($data);

        return redirect()->route('sales.customers.index')
            ->with('success', "Customer '{$data['name']}' created successfully.");
    }

    public function show(Customer $customer)
    {
        $customer->load('sales');
        $recentSales = $customer->sales()->with('items')->latest()->limit(10)->get();
        $totalSpent  = $customer->sales()->where('status','completed')->sum('total');
        return view('sales.customers.show', compact('customer', 'recentSales', 'totalSpent'));
    }

    public function edit(Customer $customer)
    {
        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'phone'         => 'required|string|max:20',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:300',
            'city'          => 'nullable|string|max:100',
            'customer_type' => 'required|in:individual,business',
            'status'        => 'required|in:active,inactive',
            'notes'         => 'nullable|string|max:500',
        ]);

        $customer->update($data);
        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete: customer has sales records. Deactivate instead.');
        }
        $customer->delete();
        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer deleted.');
    }
}
