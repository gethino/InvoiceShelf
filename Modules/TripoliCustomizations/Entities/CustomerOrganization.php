<?php

namespace Modules\TripoliCustomizations\Entities;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrganization extends Model
{
    protected $fillable = ['company_id', 'name', 'notes'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
