<?php

namespace App\Http\Controllers\Api;

use App\Repositories\Interfaces\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            'name'             => 'required|min:5|max:80',
            'email'            => 'required|unique:users,email,NULL,id,deleted_at,NULL',
            'password'         => [
                'required',
                'min:8',
                'max:80',
            ],
            'password_confirm' => 'required|same:password',

        ];
        $validator = Validator::make($payload, $rules);
        if ($validator->fails()) {
            return $this->response($validator);
        }
        $payload['password'] = Hash::make($payload['password']);
        $user                = $this->userRepository->create($payload);
        return $this->response($user->toArray());
    }

    public function login(Request $request)
    {
        $payload   = request(['email', 'password']);
        $validator = Validator::make($payload, [
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return $this->response($validator);
        }
        // if (!$token = auth()->attempt($payload)) {
        if (!$token = auth('api')->attempt($payload)) {
            $data = [
                'message' => 'These credentials do not match our records.'
            ];
            return $this->response($data, null, null, [], 422);
        }
        $data = [
            'message' => 'Successfully logged in.',
            'token'   => $token,
        ];
        return $this->response($data);
        // return $this->respondWithToken($token);
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
