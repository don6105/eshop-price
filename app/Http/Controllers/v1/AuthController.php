<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        return UserModel::create([
            'name'    => $req->get('name'),
            'email'   => $req->get('email'),
            'password'=> Hash::make($req->get('password'))
        ]);
    }

    public function login(Request $req)
    {
        if(!Auth::attempt($req->only(['email','password']))){
            return response(["msg" => "Bad Credential"], Response::HTTP_UNAUTHORIZED);
        }
        // create token and save it to personal_access_tokens.
        $token  = Auth::user()->createToken($req->get('email'))->plainTextToken;
        // response to client with cookie.
        $cookie = cookie('token', $token, 60 * 24);
        return response(['token' => $token])->withCookie($cookie); 
    }

    public function user(Request $req)
    {
        return Auth::user();
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        $cookie = Cookie::forget("token");
        return response(['msg' => 'Success'])->withCookie($cookie);
    }
}
