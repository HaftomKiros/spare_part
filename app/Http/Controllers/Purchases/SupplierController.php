<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchases');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('supplier_code', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('company', 'like', "%{$request->search}%");
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->latest()->paginate(20)->withQueryString();
        return view('purchases.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $code = Supplier::generateCode();
        return view('purchases.suppliers.create', compact('code'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:150',
            'company'        => 'nullable|string|max:200',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:300',
            'city'           => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string|max:500',
        ]);

        $data['supplier_code'] = Supplier::generateCode();
        Supplier::create($data);

        return redirect()->route('purchases.suppliers.index')
            ->with('success', "Supplier '{$data['name']}' created successfully.");
    }

    public function show(Supplier $supplier)
    {
        $recentPurchases = $supplier->purchases()->with('user')->latest()->limit(10)->get();
        $totalPurchased  = $supplier->purchases()->sum('total');
        $totalPaid       = $supplier->purchases()->sum('paid_amount');
        return view('purchases.suppliers.show', compact('supplier', 'recentPurchases', 'totalPurchased', 'totalPaid'));
    }

    public function edit(Supplier $supplier)
    {
        return view('purchases.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:150',
            'company'        => 'nullable|string|max:200',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:300',
            'city'           => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string|max:500',
        ]);

        $supplier->update($data);
        return redirect()->route('purchases.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Cannot delete: supplier has purchase records. Deactivate instead.');
        }
        $supplier->delete();
        return redirect()->route('purchases.suppliers.index')
            ->with('success', 'Supplier deleted.');
    }
}
