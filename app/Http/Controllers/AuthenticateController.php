<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use JWTAuth;
use App\Models\User;

class AuthenticateController extends Controller
{
    /**
     * jwt token.
     *
     * @var string
     */
    protected $token;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        // 登录成功，生成token
        $this->createToken();

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['token' => $this->token]);
    }

    /**
     * create jwt token.
     * 
     * @return void
     */
    protected function createToken()
    {
        $user = User::first();
        $token = JWTAuth::fromUser($user);
        $this->token = $token;
    }

}
