<?php

namespace App\Http\Controllers\Api;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

    }

    public function register(Request $request)
    {
        $payload = $request->all();
        $rules   = [
            'name'             => 'required|min:8|max:80',
            'email'            => 'required|unique:users,email,NULL,id,deleted_at,NULL',
            'password'         => [
                'required',
                'min:8',
                'max:80',
            ],
            'password_confirm' => 'required|same:password',

        ];
        $validator = $this->validate($request, $rules);
        if ($validator->fails()) {
            return $this->response($validator);
        }
        $payload['password'] = Hash::make($payload['password']);
        $user                = $this->userRepository->create($payload);

        return $this->response($user->toArray());
        // $token = auth()->login($user);
        // return $this->respondWithToken($token);
    }

    public function login()
    {
        $payload = request(['email', 'password']);
        $rules   = [
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ];
        $validator = $this->validate($request, $rules);

        if ($validator->fails()) {
            return $this->response($validator);
        }

        if (!$token = auth()->attempt($payload)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
        ]);
    }
}
