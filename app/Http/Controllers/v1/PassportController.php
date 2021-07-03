<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\User as UserModel;

class PassportController extends Controller
{
    private $succCode = 200;

    public function register(Request $request)
    {
        $error_msg = [
            'required' => 'The :attribute field is required.',
            'unique'   => 'The :attribute has been used.',
            'same'     => 'The :attribute and :other are different.',
            'max'      => 'The length of :attribute should less than :max.',
            'min'      => 'The length of :attribute should more than :min.'
        ];
        $validator = Validator::make($request->all(), [
            'name'             => 'required|min:3|max:255',
            'email'            => 'required|email|max:255|unique:users',
            'password'         => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ], $error_msg);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user  = UserModel::create($input);
        $success['token'] =  $user->createToken('eshop-price')->accessToken;
        $success['name']  =  $user->name;
        return response()->json(['success' => $success], $this->succCode);
    }

    public function login(Request $request) {
        if(Auth::attempt($request->only(['email', 'password']))) {
            $user = Auth::user();
            $success['token'] = $user->createToken('eshop-price')->accessToken;
            return response()->json(['success' => $success], $this->succCode);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    public function logout(Request $request) 
    {
        if (Auth::check()) {
            auth()->user()->token()->revoke();
            return response()->json(['success' => 'logged out'], $this->succCode);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }
}
