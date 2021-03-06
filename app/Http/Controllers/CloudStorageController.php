<?php

namespace App\Http\Controllers;

use App\Events\FileDelete;
use App\Events\FileMove;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\File;
use App\Http\Requests;
use App\Events\FileCreate;
use App\Events\FileDownload;
use App\Events\FileUpload;

class CloudStorageController extends Controller
{
    /**
     * user model.
     * 
     * @var
     */
    protected $user;

    /**
     * user root path.
     * 
     * @var string
     */
    protected $userRootPath;

    /**
     * define user and user root path.
     * 
     * CloudStorageController constructor.
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->userRootPath = '/' . $user->stu_id;
    }

//    public function index()
//    {
////        return response()->json($this->listContents('/dir/'));
//    }

    /**
     * Get all files. (include sub files)
     * 
     * @param $path
     * @param int $status
     * @return array
     */
    public function getAllFiles($path, $status = 1)
    {
        $files = [];

        $fileCollections = File::where([
            'username' => $this->user->name,
            'status' => $status,
            'path' => $path,
        ])->get();

        foreach ($fileCollections as $fileCollection) {
            $filename = $fileCollection->filename;
            $type = $fileCollection->type;
            $size = $fileCollection->size;
            $basename = $fileCollection->basename;
            $updated_at = $fileCollection->updated_at;
            array_push($files, compact('filename', 'type', 'size', 'path', 'updated_at'));

            if ($type == 'dir') {
                $subFiles = $this->getAllFiles($basename, $status);
                $files = array_collapse([$files, $subFiles]);
            }
        }

        return $files;
    }

    /**
     * Get files.
     * 
     * @param $path
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
            $updated_at = $fileCollection->updated_at;
            array_push($files, compact('filename', 'type', 'size', 'path', 'updated_at'));
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

    /**
     * Determine if the file exists.
     * 
     * @param $path
     * @param $filename
     * @return bool
     */
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
     * @param $directory
     * @return bool
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

        $listenerArray = event(new FileCreate($this->user->uid,
            $this->userRootPath . '/' . $directory));
        if (!$listenerArray[0])
            return false;

        if (!$newDirectory->save())
            return false;

        return true;
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
//    public function delete($files)
//    {
//        foreach ($files as $file) {
//            // 删目录
//            $dir = File::where([
//                'username' => $this->user->name,
//                'basename' => $file,
//                'type' =>'dir',
//                'status' => 1,
//            ])->first();
//
//            if (!!$dir)
//                if (!$this->deleteDirectory($file))
//                    return false;
//
//            // 删文件
//            File::where([
//                'username' => $this->user->name,
//                'basename' => $file,
//                'type' => 'file',
//                'status' => 1,
//            ])->update(['status' => 0]);
//        }
//
//        foreach ($files as $file) {
//            $bool = File::where([
//                'username' => $this->user->name,
//                'status' => 1,
//                'basename' => $file,
//            ])->update(['status' => 0]);
//
////            if (!$bool)
////                return false;
//        }
//
//        return true;
//    }

    /**
     * Delete Directory.
     * 
     * @param $directory
     * @return bool
     */
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
                $this->deleteDirectory($file->basename);
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
     * Move the files.
     * 
     * @param $info
     * @return bool|\Illuminate\Http\JsonResponse
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
                'basename' => $this->setRoot($info['to']) . '/' .basename($file),
            ]);

            $listenerArray = event(new FileMove($this->user->id,
                $this->userRootPath . $file,
                $this->userRootPath . $info['to'] . basename($file)));

            if (!$bool)
                return response()->json(['error' => 'failed']);
        }

        return true;
    }

    /**
     * Set root path.
     * 
     * @param $path
     * @return string
     */
    private function setRoot($path)
    {
        $path = dirname($path);

        if ($path == '/' || $path == '\\')
            return '';
        else
            return $path;
    }

    /**
     * Change file's name.
     * 
     * @param $oldName
     * @param $newName
     * @return bool
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
            'basename' => $this->setRoot($oldName) . '/' .basename($newName)
        ]);

        $listenerArray = event(new FileMove($this->user->stu_id,
            $this->userRootPath . '/' . $oldName,
            $this->userRootPath . '/' . dirname($oldName) . '/' .basename($newName)));

        if (!$bool)
            return false;

        return true;
    }

    /**
     * Determine if the directory had been already exists.
     * 
     * @param $directory
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

    /**
     * Force delete files.
     * 
     * @param $files
     * @return bool
     * @throws \Exception
     */
    public function forceDelete($files)
    {
        foreach ($files as $file) {
            $type = File::where([
                'username' => $this->user->name,
                'status' => 1,
                'basename' => $file,
            ])->first()->type;

            // 递归删除目录文件
            if ($type == 'dir') {
                $all = $this->getAllFiles($file, 1);

                foreach ($all as $lowFile) {
                    $bool = File::where([
                        'username' => $this->user->name,
                        'basename' => $lowFile['path'] . '/' .$lowFile['filename'],
                    ])->delete();

                    if (!$bool) {
                        return false;
                    }
                }
            }

            // 删除当前文件
            $bool2 = File::where([
                'username' => $this->user->name,
                'status' => 1,
                'basename' => $file,
            ])->delete();
            if (!$bool2)
                return false;

            $listenerArray = event(new FileDelete($this->user->name, $this->userRootPath . '/' . $file));
        }

        return true;
    }

    /**
     * get recycle
     * 
     * @return bool
     */
//    public function getRecycleBin()
//    {
//        $files = File::where([
//            'username' => $this->user->name,
//            'status' => 0,
//        ])->get();
//
//        return $files;
//    }

//    public function recover($files)
//    {
//
//    }

    public function upload($request)
    {
        $up_path = $request->up_path;
        $file = $request->file('files');

        if ($file->isValid()) {

            $newFile = new File();
            $newFile->filename = $file->getClientOriginalName();
            $newFile->type = $file->getClientMimeType();
            $newFile->pid = $this->user->uid;
            $newFile->size = $file->getClientSize();
            $newFile->username = $this->user->name;
            $newFile->status = 1;
            $newFile->path = $up_path;

        }



        if (!$newFile->save())
            return false;

        $result = event(new FileUpload($file->getClientOriginalName(),$file->getRealPath(), $up_path));
        if ($result)
            return true;

        return false;
    }

    public function download($info)
    {
        if ($this->user!=$info['user'])
            return false;

        return event(new FileDownload($info['file']));
    }

}
