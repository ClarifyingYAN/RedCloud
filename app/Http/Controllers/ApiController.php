<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\CloudStorageController as Cloud;

class ApiController extends Controller
{
    /**
     * user.
     *
     * @var \App\Models\User|null
     */
    private $user;

    /**
     * Instance of cloudStorageController class.
     *
     * @var CloudStorageController
     */
    private $cloud;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (Auth::check()) {        // 否则 php artisan route:list 将会报出 trying to get none object 错误
            $this->user = Auth::user();

            $this->cloud = new Cloud($this->user);
        }

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

    /**
     * Get files.
     *
     * @param string $directory
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiles($directory = 'Lw==')
    {
        $directory = base64_decode($directory);
        $files = $this->cloud->listContents($directory);

        return response()->json($files);
    }

    /**
     * Get all files.
     *
     * @param string $directory
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllFiles($directory = 'Lw==')
    {
        $directory = base64_decode($directory);
        $files = $this->cloud->getAllFiles($directory);

        return response()->json($files);
    }

    /**
     * Move the files.
     *
     * @param Requests\MoveRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function move(Requests\MoveRequest $request)
    {
        $info = json_decode($request->movedFiles, true);
        if (!$this->cloud->cloudMove($info))
            return response()->json(['error' => 'failed']);
        
        return response()->json(['status' => '200']);
    }

    /**
     * Rename the file.
     *
     * @param Requests\RenameRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rename(Requests\RenameRequest $request)
    {
        $newName = $request->newName;
        $oldName = $request->oldName;

        if (!$this->cloud->cloudChangeName($oldName, $newName))
            return response()->json(['error' => 'failed']);
        
        return response()->json(['status' => '200']);
    }

    /**
     * Create the directory.
     *
     * @param null $directory
     * @return \Illuminate\Http\JsonResponse
     */
    public function create($directory = null)
    {
        if ($directory === null)
            return response()->json(['error' => 'directory name can not be empty']);

        $directory = base64_decode($directory);

        if (!$this->cloud->create($directory))
            return response()->json(['error' => 'failed']);

        return response()->json(['status' => '200']);
    }

//    public function delete(Requests\SoftDeleteRequest $request)
//    {
//        $files = json_decode($request->deletedFiles);
//
//        // 判断json解析出的file只是一个字符串而不是数组，如果是字符串则变为数组
//        if (!is_array($files))
//            $files = [$files];
//        
//        if (!$this->cloud->delete($files))
//            return response()->json(['error' => 'failed']);
//
//        return response()->json(['status' => '200']);
//    }

    /**
     * Force delete.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $files = json_decode($request->deletedFiles);
        
        if (!$this->cloud->forceDelete($files))
            return response()->json(['failed']);

        return response()->json(['status' => '200']);
    }
    
//    public function getRecycle()
//    {
//        $files = $this->cloud->getRecycleBin();
//        
//        return response()->json($files);
//    }
    
//    public function recover(Request $request)
//    {
//        $files = json_decode($request->recoverFiles);
//        if (!$this->cloud->recover($files))
//            return response()->json(['error' => 'failed']);
//        
//        return response()->json(['status' => '200']);
//    }

}
