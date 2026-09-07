<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserIndexRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(
        AdminUserIndexRequest $request
    ): AnonymousResourceCollection {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->value();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where(
                'role',
                $request->string('role')->value()
            );
        }

        match ($request->string('sort')->value()) {
            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name' => $query
                ->orderBy('name')
                ->orderBy('id'),

            default => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };

        $users = $query->paginate(15);

        return AdminUserResource::collection($users);
    }

    public function show(User $user): AdminUserResource
    {
        return new AdminUserResource($user);
    }

    public function update(
        UpdateAdminUserRequest $request,
        User $user
    ): JsonResponse {
        $data = $request->validated();

        $user->update($data);

        return (new AdminUserResource($user->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
