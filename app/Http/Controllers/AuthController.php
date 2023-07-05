<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated());

        $token = $user->createToken('User Passport Token')->accessToken;

        return response()->json(["token" => $token], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (Auth::attempt($request->validated())) {
            $user = User::where('email', $request->email)->first();

            $token = $user->createToken('User Passport Token')->accessToken;

            return response()->json(["token" => $token], Response::HTTP_OK);
        } else {
            return response()->json(["message" => 'Invalid credentials'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
