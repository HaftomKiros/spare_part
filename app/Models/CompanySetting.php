<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'company_logo',
        'currency',
        'currency_symbol',
        'tax_number',
        'website',
    ];

    /**
     * Get the singleton company settings record.
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'company_name'    => 'Abush Spare Part',
            'currency'        => 'ETB',
            'currency_symbol' => 'Br',
        ]);
    }
}
