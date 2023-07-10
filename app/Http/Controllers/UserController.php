<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function update(UserUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $emailIsUpdated = $this->userService->emailIsUpdated($user, $request->validated());

        if ($emailIsUpdated) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } else {
            return response()->json(["message" => 'You do not own this email'], Response::HTTP_BAD_REQUEST);
        }
    }
}
