<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $companyId = (int) $request->header('company');
        $actingUser = $request->user();
        $isActingUser = $actingUser?->is($this->resource) ?? false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'contact_name' => $this->contact_name,
            'company_name' => $this->company_name,
            'website' => $this->website,
            'enable_portal' => $this->enable_portal,
            'currency_id' => $this->currency_id,
            'facebook_id' => $this->facebook_id,
            'google_id' => $this->google_id,
            'github_id' => $this->github_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'avatar' => $this->avatar,
            'is_owner' => $this->isOwner(),
            'is_super_admin' => $this->isPrivilegedForCompany($companyId),
            'can_switch_companies' => $isActingUser && $this->canSwitchCompanies(),
            'can_create_company' => $isActingUser && $this->canCreateCompany(),
            'can_manage_users' => $isActingUser && $this->canManageUsersForCompany($companyId),
            'can_manage_user_companies' => $isActingUser && $this->canManageUserCompaniesForCompany($companyId),
            'can_edit' => $actingUser?->can('update', $this->resource) ?? false,
            'can_delete' => $actingUser?->can('delete', $this->resource) ?? false,
            'roles' => $this->roles,
            'formatted_created_at' => $this->formattedCreatedAt,
            'currency' => $this->when($this->currency()->exists(), function () {
                return new CurrencyResource($this->currency);
            }),
            'companies' => $this->when($this->companies()->exists(), function () {
                return CompanyResource::collection($this->companies);
            }),
        ];
    }
}
