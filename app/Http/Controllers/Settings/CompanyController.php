<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = CompanySetting::getInstance();
        return view('settings.company.edit', compact('company'));
    }

    public function update(Request $request)
    {
        // Validate everything including logo before touching the filesystem
        $data = $request->validate([
            'company_name'    => 'required|string|max:200',
            'company_address' => 'nullable|string|max:500',
            'company_phone'   => 'nullable|string|max:30',
            'company_email'   => 'nullable|email|max:150',
            'currency'        => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
            'tax_number'      => 'nullable|string|max:50',
            'website'         => 'nullable|url|max:200',
            'company_logo'    => 'nullable|image|max:2048',
        ]);

        $company = CompanySetting::getInstance();

        // Handle logo upload — store new file first, then delete old one
        if ($request->hasFile('company_logo')) {
            $newPath = $request->file('company_logo')->store('logos', 'public');

            if ($company->company_logo && $company->company_logo !== $newPath) {
                Storage::disk('public')->delete($company->company_logo);
            }

            $data['company_logo'] = $newPath;
        }

        // Remove the logo key from $data if no file was uploaded
        // (prevents overwriting existing logo with null)
        if (!isset($data['company_logo'])) {
            unset($data['company_logo']);
        }

        $company->update($data);

        // Clear cache so sidebar/header picks up new logo and name immediately
        Cache::forget('company_settings');

        return redirect()->route('settings.company')
            ->with('success', 'Company settings updated successfully.');
    }
}
