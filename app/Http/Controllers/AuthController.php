<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        $token = $user->createToken('User Passport Token')->accessToken;

        return response()->json(["token" => $token], Response::HTTP_CREATED);
    }
}
