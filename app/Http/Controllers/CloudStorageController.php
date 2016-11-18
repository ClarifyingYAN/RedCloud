<?php

namespace App\Http\Controllers;

use App\Events\FileDelete;
use App\Events\FileMove;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\File;
use App\Http\Requests;
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
    public function create($directory)
    {
        $parentDirectory = dirname($directory);

        // 自己挖的坑 linux和win下路径分隔符
        if ($parentDirectory == '\\')
            $parentDirectory = '/';

        // Determine if the parent directory exists.
        if (!$this->hasDirectory($parentDirectory))
            return false;

        //  Determine if the directory exists.
        if ($this->hasDirectory($directory))
            return false;

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
            return false;

        if (!$newDirectory->save())
            return false;

        return true;
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
    public function delete($files)
    {
        foreach ($files as $file) {
            // 删目录
            $dir = File::where([
                'username' => $this->user->name,
                'basename' => $file,
                'type' =>'dir',
                'status' => 1,
            ])->first();

            if (!!$dir)
                if (!$this->deleteDirectory($file))
                    return false;

            // 删文件
            File::where([
                'username' => $this->user->name,
                'basename' => $file,
                'type' => 'file',
                'status' => 1,
            ])->update(['status' => 0]);
        }

        return true;
    }

    public function deleteDirectory($directory)
    {
        $directory = File::where([
            'basename' => $directory,
        ])->first();

        if (!$directory)
            return false;

        $files = File::where([
            'username' => $this->user->name,
            'path' => $directory->basename,
            'type' => 'dir',
            'status' => 1,
        ])->get();

        if (!!$files)
            foreach ($files as $file) {
                $this->deleteDirectory($directory);
            }

        File::where([
            'username' => $this->user->name,
            'status' => 1,
            'path' => $directory->basename,
        ])->update(['status' => 0]);

        File::whereBasename($directory->basename)->update(['status' => 0]);

        return true;
    }

    /**
     * ` files.
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

            $listenerArray = event(new FileMove($this->user->id, $file, $info['to'] . $file));

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
    public function cloudChangeName($oldName, $newName)
    {
        // Determine if the old name exists.
        if ($this->hasFile(dirname($oldName), basename($newName)))
            return false;
        
        // Determine if the new name exists.
        if ($this->hasFile(dirname($oldName), basename($newName)))
            return false;

        $bool = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'basename' => $oldName,
        ])->update([
            'filename' => basename($newName),
            'basename' => dirname($oldName) . '/' .basename($newName)
        ]);

        $listenerArray = event(new FileMove($this->user->stu_id, $oldName, dirname($oldName) . '/' .basename($newName)));

        if (!$bool)
            return false;

        return true;
    }

    /**
     * Determine if the directory had been already exists.
     *
     * @param $basename
     * @return bool
     */
    protected function hasDirectory($directory)
    {
        // 自己挖的坑 linux和win下路径分隔符
        if ($directory == '\\')
            $directory = '/';

        if ($directory == '/')
            return true;

        $directory = File::where([
            'username' => $this->user->name,
            'status' => 1,
            'type' => 'dir',
            'basename' => $directory,
        ])->first();

        if (!$directory)
            return false;

        return true;
    }

    public function forceDelete($files)
    {
        foreach ($files as $file) {
            $bool = File::where([
                'username' => $this->user->name,
                'basename' => $file,
            ])->delete();

            if (!$bool)
                return false;
            
            $listenerArray = event(new FileDelete($this->user->name, $file));
        }

        return true;
    }

}
