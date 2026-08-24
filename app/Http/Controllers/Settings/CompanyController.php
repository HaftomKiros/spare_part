<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = CompanySetting::getInstance();
        return view('settings.company.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'    => 'required|string|max:200',
            'company_address' => 'nullable|string|max:500',
            'company_phone'   => 'nullable|string|max:30',
            'company_email'   => 'nullable|email|max:150',
            'currency'        => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
            'tax_number'      => 'nullable|string|max:50',
            'website'         => 'nullable|url|max:200',
        ]);

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            $request->validate(['company_logo' => 'image|max:2048']);
            $path = $request->file('company_logo')->store('logos', 'public');
            $data['company_logo'] = $path;
        }

        $company = CompanySetting::getInstance();
        $company->update($data);

        // Clear cache so sidebar picks up new name
        Cache::forget('company_settings');

        return redirect()->route('settings.company')
            ->with('success', 'Company settings updated successfully.');
    }
}
