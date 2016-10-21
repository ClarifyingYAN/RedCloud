<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use JWTAuth;
use App\Models\User;

class HomeController extends Controller
{
    protected $token;
    
    protected $user;

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

        // 返回token
        $this->sendResponse();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response($array, 200)->withCookie('token', $this->token);
    }

    protected function sendResponse()
    {

    }

    protected function createToken()
    {
        $user = User::first();
        $token = JWTAuth::fromUser($user);
        $this->token = $token;
    }

    public function post(Request $request)
    {
        $input = $request->all();
//        var_dump($input);
        return response()->json($input);
    }
}
