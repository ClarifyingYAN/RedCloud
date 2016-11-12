<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\File;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;

class CloudStorageController extends FileController
{
    /**
     * User
     *
     * @var \App\Models\User|null
     */
    protected $user;

    protected $files;

    public function __construct()
    {
        parent::__construct();
        $this->user = Auth::user();
    }

    public function index()
    {

//        return response()->json($this->listContents('/dir/'));
    }

    protected function allFiles($directory)
    {
        $directory = base64_decode($directory);

        return response()->json($this->getAllFiles($directory));
    }

    protected function getAllFiles($path)
    {
        $files = [];

        $fileCollections = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'path' => $path,
        ])->get();

        foreach ($fileCollections as $fileCollection) {
            $filename = $fileCollection->filename;
            $type = $fileCollection->type;
            $size = $fileCollection->size;
            $basename = $fileCollection->basename;
            array_push($files, compact('filename', 'type', 'size', 'path'));

            if ($type == 'dir') {
                $subFiles = $this->getAllFiles($basename);
                $files = array_collapse([$files, $subFiles]);
            }
        }

        return $files;
    }

    /**
     * List current contents.
     *
     * @return array
     */
    protected function listContents($path)
    {
        $files = [];

        $fileCollections = File::where('username', $this->user->name)
            ->where('path', $path)
            ->where('status', '1')
            ->get();

        foreach ($fileCollections as $fileCollection) {
            $filename = $fileCollection->filename;
            $type = $fileCollection->type;
            $size = $fileCollection->size;
            array_push($files, compact('filename', 'type', 'size', 'path'));
        }

        return $files;
    }

    /**
     * store the upload file.
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
//    public function store(Request $request)
//    {
//        $fileInfo = $request->all();
//
//        if (!$this->hasFile($fileInfo['path'], $fileInfo['path']))
//            return response()->json(['status'=>'403']);
//
//        // store the uploaded file's info.
//        $file = new File;
//        $file->filename = $fileInfo['filename'];
//        $file->type = $fileInfo['type'];
//        $file->path = $fileInfo['path'];
//        $file->pid = $this->user->uid;
//        $file->username = $this->user->name;
//        $file->size = 1;
//        $file->status = 1;
//
//        if (!$file->save())
//            return false;
//
//        return response()->json(['status'=> 200]);
//    }

    protected function hasFile($path, $filename)
    {
        $file = File::where('username', $this->user->name)
            ->where('path', $path)
            ->where('filename', $filename)
            ->first();

        if ($file == null)
            return false;

        return true;
    }

    /**
     * Create directory.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $directory = base64_decode(Input::get('directory'));
        $parentDirectory = $this->getDirectoryFromPath($directory);

        // Determine if the parent directory exists.
        if (!$this->hasDirectory($parentDirectory))
            return response()->json(['error' => 'dont have the parent directory']);

        //  Determine if the directory exists.
        if (!$this->hasDirectory($directory))
            return response()->json(['error' => 'have had the directory']);

        $newDirectory = new File;
        $newDirectory->filename = $this->getFilenameFromPath($directory);
        $newDirectory->type = 'dir';
        $newDirectory->pid = $this->user->uid;
        $newDirectory->size = 0;
        $newDirectory->username = $this->user->name;
        $newDirectory->status = 1;
        $newDirectory->basename = $directory;
        $newDirectory->path = $parentDirectory;

        if (!$newDirectory->save())
            return response()->json(['status' => '200']);

        return response()->json(['status' => '200']);
    }

    /**
     * Show the directory list.
     *
     * @param $directory
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($directory)
    {
        $directory = base64_decode($directory);
        $files = $this->listContents($directory);

        return response()->json($files);
    }

    public function update(Request $request)
    {

    }

    /**
     * Soft delete.
     * 还未完成：
     * 1. 判断是否是json传入
     * 2. 如果某一个文件错误（如不存在）的解决方案，或者返回错误机制
     * 3. 优化数据库删除的方法
     * 4. 事务执行
     * 5. 当删除后，创建一个新的同名文件，然后进行恢复该如何处理
     *
     * @param Requests\SoftDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Requests\SoftDeleteRequest $request)
    {
        $files = $request->deletedFiles;

        $files = json_decode($files);

        // 判断json解析出的file只是一个字符串而不是数组，如果是字符串则变为数组
        if (!is_array($files))
            $files = [$files];

        foreach ($files as $file) {
            $bool = File::where([
                'username' => $this->user->name,
                'basename' => $file,
                'status' => 1,
            ])->update(['status' => 0]);

            if (!$bool)
                return response()->json(['delete' => 'failed']);
        }

        return response()->json(['status', '200']);
    }

    /**
     * move files.
     * 缺陷同删除方法
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cloudMove(Requests\MoveRequest $request)
    {
        $info = json_decode($request->movedFiles, true);

        foreach ($info['files'] as $file) {
            $bool = File::where([
                'username' => $this->user->name,
                'status' => 1,
                'basename' => $file
            ])->update([
                'path' => $info['to'],
                'basename' => $info['to'] . $file,
            ]);

            if (!$bool)
                return response()->json(['error' => 'failed']);
        }

        return response()->json(['status', '200']);
    }

    /**
     * Change file's name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cloudChangeName(Request $request)
    {
        $oldName = $request->oldName;
        $newName = $request->newName;

        // Determine if the old name exists.
        if ($this->hasFile($this->getDirectoryFromPath($oldName), $this->getFilenameFromPath($newName)))
            return response()->json(['error' => 'don\'t have the old file']);
        
        // Determine if the new name exists.
        if ($this->hasFile($this->getDirectoryFromPath($oldName), $this->getFilenameFromPath($newName)))
            return response()->json(['error' => 'have had file']);

        $bool = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'basename' => $oldName,
        ])->update([
            'filename' => $this->getFilenameFromPath($newName),
            'basename' => $newName,
        ]);

        if (!$bool)
            return response()->json(['error' => 'failed']);

        return response()->json(['status' => '200']);
    }

    /**
     * Determine if the directory had been already exists.
     *
     * @param $basename
     * @return bool
     */
    protected function hasDirectory($basename)
    {
        if ($basename == '/')
            return true;

        $directory = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'type' => 'dir',
            'basename' => $basename,
        ])->first();

        if (!!$directory)
            return false;

        return true;
    }

    public function forceDelete()
    {
        
    }

    public function edit(Request $request)
    {

    }

}
