<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\CloudStorageController as Cloud;

class ApiController extends Controller
{

    private $user;
    
    private $cloud;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->cloud = new Cloud($this->user);
    }

    /**
     * return api guide info.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            [
                'api_url' => 'https://www.redcloud.com/api',
            ]
        );
    }

    public function getFiles($directory = 'Lw==')
    {
        $directory = base64_decode($directory);
        $files = $this->cloud->listContents($directory);

        return response()->json($files);
    }
    
    public function getAllFiles($directory = 'Lw==')
    {
        $directory = base64_decode($directory);
        $files = $this->cloud->getAllFiles($directory);

        return response()->json($files);
    }

    public function move(Requests\MoveRequest $request)
    {
        $info = json_decode($request->movedFiles, true);
        if (!$this->cloud->cloudMove($info))
            return response()->json(['error' => 'failed']);
        
        return response()->json(['status', '200']);
    }
}
