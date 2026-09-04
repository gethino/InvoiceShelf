<?php

namespace App\Http\Controllers\V1\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Silber\Bouncer\Database\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $user = $request->user();

        $users = User::whereCompany()
            ->applyFilters($request->all())
            ->where('id', '<>', $user->id)
            ->latest()
            ->paginate($limit);

        return UserResource::collection($users)
            ->additional(['meta' => [
                'user_total_count' => User::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\UserRequest  $request
     * @return JsonResponse
     */
    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $companyId = (int) $request->header('company');
        $restrictToActiveCompany = ! $request->user()->canManageUserCompaniesForCompany($companyId);

        if ($restrictToActiveCompany) {
            $this->authorizeManagerPayload($request, $companyId);
        }

        $user = User::createFromRequest($request, $restrictToActiveCompany ? $companyId : null);

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\UserRequest  $request
     * @return JsonResponse
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $companyId = (int) $request->header('company');
        $restrictToActiveCompany = ! $request->user()->canManageUserCompaniesForCompany($companyId);

        if ($restrictToActiveCompany) {
            $this->authorizeManagerPayload($request, $companyId);
        }

        $user->updateFromRequest($request, $restrictToActiveCompany ? $companyId : null);

        return new UserResource($user);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function delete(DeleteUserRequest $request)
    {
        $this->authorize('delete multiple users', User::class);

        if ($request->users) {
            // Scope the candidate ids to members of the acting company so a user
            // from one company cannot delete accounts belonging to another.
            $ids = User::whereCompany()
                ->whereIn('id', $request->users)
                ->pluck('id')
                ->toArray();

            if ($ids) {
                User::deleteUsers($ids);
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function authorizeManagerPayload(UserRequest $request, int $companyId): void
    {
        $companies = collect($request->validated('companies'));
        $company = $companies->first();

        abort_if(
            $companies->count() !== 1
            || (int) ($company['id'] ?? 0) !== $companyId
            || ($company['role'] ?? null) === 'super admin',
            403
        );

        abort_unless(
            Role::query()
                ->where('scope', $companyId)
                ->where('name', $company['role'])
                ->exists(),
            403
        );
    }
}
