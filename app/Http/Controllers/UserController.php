<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserDeleteRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $this->userService->updateEmail($user, $request->email);

        return response()->json($user, Response::HTTP_OK);
    }

    public function destroy(UserDeleteRequest $request, User $user): JsonResponse
    {
        $this->userService->deleteUser($user);
        $this->userService->deleteUserMailSend($user);

        return response()->json($user, Response::HTTP_OK);
    }
}
