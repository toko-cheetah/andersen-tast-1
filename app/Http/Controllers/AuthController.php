<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->userService->store($request->validated());

        $token = $user->createToken('User Passport Token')->accessToken;

        return response()->json(["token" => $token], Response::HTTP_CREATED);
    }
}
