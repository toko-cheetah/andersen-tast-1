<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\ForgotPasswordMail;
use App\Models\ResetPassword;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated())->first();

        $tokenData = $this->userService->forgotPassword($user);

        try {
            Mail::to($user->email)->send(new ForgotPasswordMail($user->email, $tokenData->token));

            return response()->json(['message' => 'Email sent'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to send email'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $tokenData = ResetPassword::where('token', $request->token)->first();

        $tokenCreatedAt = Carbon::parse($tokenData->created_at);
        $currentDateTime = Carbon::now();

        $this->userService->deleteToken($tokenData);

        if ($tokenCreatedAt->diffInMinutes($currentDateTime) < 120) {
            $this->userService->resetPassword($tokenData, $request->password);

            return response()->json(['message' => 'Password updated'], Response::HTTP_CREATED);
        } else {
            return response()->json(['message' => 'Token is outdated'], Response::HTTP_BAD_REQUEST);
        }
    }
}
