<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->validated('role'), fn (Builder $query, string $role) => $query->where('role', $role))
            ->when($request->validated('search'), fn (Builder $query, string $term) => $this->search($query, $term))
            ->orderBy('name')
            ->paginate($request->perPage());

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        return UserResource::make(User::create($request->validated()));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return UserResource::make($user);
    }

    /** @param  Builder<User>  $query */
    private function search(Builder $query, string $term): void
    {
        $escaped = addcslashes(trim($term), '%_\\');

        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$escaped}%")
            ->orWhere('email', 'like', "%{$escaped}%"));
    }
}
