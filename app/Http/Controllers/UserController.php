<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): JsonResponse
    {
        $users = User::all();
        $usersEmails = $users->pluck('email')->all();

        return response()->json(['users' => $usersEmails], Response::HTTP_OK);
    }

    public function get(User $user): UserResource
    {
        $authenticatedUser = auth()->user();

        Gate::authorize('update', [$user, $authenticatedUser]);

        return new UserResource($user);
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $this->userService->updateEmail($user, $request->email);

        return response()->json($user, Response::HTTP_OK);
    }
}
