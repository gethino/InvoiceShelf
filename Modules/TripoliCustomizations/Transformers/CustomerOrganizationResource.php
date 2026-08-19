<?php

namespace Modules\TripoliCustomizations\Transformers;

use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'company_id' => $this->company_id,
            'customers_count' => $this->whenCounted('customers'),
            'customers' => CustomerResource::collection($this->whenLoaded('customers')),
        ];
    }
}
