<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\File;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use App\Events\FileCreate;

class CloudStorageController extends Controller
{
    /**
     * User
     *
     * @var \App\Models\User|null
     */
    protected $user;

    protected $userRootPath;

    protected $files;

    public function __construct($user)
    {
        $this->user = $user;
        $this->userRootPath = $user->stu_id;
    }

    public function index()
    {

//        return response()->json($this->listContents('/dir/'));
    }

    public function getAllFiles($path)
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
    public function listContents($path)
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
        $parentDirectory = dirname($directory);

        // Determine if the parent directory exists.
        if (!$this->hasDirectory($parentDirectory))
            return response()->json(['error' => 'dont have the parent directory']);

        //  Determine if the directory exists.
        if ($this->hasDirectory($directory))
            return response()->json(['error' => 'have had the directory']);

        $newDirectory = new File;
        $newDirectory->filename = basename($directory);
        $newDirectory->type = 'dir';
        $newDirectory->pid = $this->user->uid;
        $newDirectory->size = 0;
        $newDirectory->username = $this->user->name;
        $newDirectory->status = 1;
        $newDirectory->basename = $directory;
        $newDirectory->path = $parentDirectory;

        $listenerArray = event(new FileCreate($this->user->uid, $directory));
        if (!$listenerArray[0])
            return response()->json(['error' => 'disk create error']);

        if (!$newDirectory->save())
            return response()->json(['error' => 'save failed']);

        return response()->json(['status' => '200']);
    }

    public function update(Request $request)
    {

    }

    /**
     * Soft delete.
     * 还未完成：
     * 1. 判断是否是json传入  xx
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
    public function cloudMove($info)
    {
        if (in_array($info['to'], $info['files'])) {
            return false;
        }

        foreach ($info['files'] as $file) {
            $bool = File::where([
                'username' => $this->user->name,
                'status' => 1,
                'basename' => $file,
            ])->update([
                'path' => $info['to'],
                'basename' => $info['to'] . $file,
            ]);

            if (!$bool)
                return response()->json(['error' => 'failed']);
        }

        return true;
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
        if ($this->hasFile(dirname($oldName), basename($newName)))
            return response()->json(['error' => 'don\'t have the old file']);
        
        // Determine if the new name exists.
        if ($this->hasFile(dirname($oldName), basename($newName)))
            return response()->json(['error' => 'have had file']);

        $bool = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'basename' => $oldName,
        ])->update([
            'filename' => basename($newName),
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

        if (!$directory)
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
