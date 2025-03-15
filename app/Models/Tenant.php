<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, SoftDeletes;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'owner_id',
        ];
    }

    protected static function booted () {
        static::creating(function ($tenant) {
            $tenant->id = (string) str()->uuid();
            $tenant->tenancy_db_name = config('tenancy.database.prefix') . str($tenant->id)->replace('-', '_');
        });
    }
}
