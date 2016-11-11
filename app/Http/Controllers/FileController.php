<?php

namespace App\Http\Controllers;

use App\Http\Requests;

class FileController extends Controller
{
    /**
     * The root path.
     *
     * @var string
     */
    protected $rootPath;
    
    public function __construct()
    {
        $this->rootPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public');
    }

    public function getSize($file)
    {
        
    }

    /**
     * Rename a file or a directory.
     *
     * @param $oldName
     * @param $newName
     * @return bool
     */
    protected function changeName($oldName, $newName)
    {
        if (file_exists($newName))
            return false;

        return rename($oldName, $newName);
    }

    /**
     * Move a file or a directory.
     *
     * @param $from
     * @param $to
     * @param bool $force
     * @return bool
     */
    protected function move($from, $to, $force = false)
    {
        // If in force mode.
        // Determine if the directory exists.
        // If not, create the directory.
        if ($force == true) {
            $directory = $this->getDirectoryFromPath($to);

            if (!file_exists($directory))
                $this->makeDirectory($directory, 0755, true);
        }

        return $this->changeName($from, $to);
    }

    /**
     * Get the filename from the path.
     *
     * @param $path
     * @return string
     */
    protected function getDirectoryFromPath($path)
    {
        $end = strrpos($path, '/');

        if (!$end)
            return '/';
        
        return substr($path, 0, $end);
    }

    /**
     * Get the directory from the path.
     *
     * @param $path
     * @return string
     */
    protected function getFilenameFromPath($path)
    {
        $start = strrpos($path, '/');

        return substr($path, $start + 1);
    }

    /**
     * Make directories recursively.
     *
     * @param $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    protected function makeDirectory($path, $mode = 0755, $recursive = false)
    {
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Delete directories recursively.
     *
     * @param $directory
     * @return bool
     */
    protected function deleteDirectory($directory)
    {
        if (!is_dir($directory)) {
            return false;
        }

        $files = $this->files($directory, true);
        foreach ($files as $file) {
            if (is_dir($file)) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }

        return rmdir($directory);
    }

    /**
     * Get files.
     *
     * @param $directory
     * @param bool $recursive
     * @return array
     */
    protected function files($directory, $recursive = false)
    {
        $files = [];

        $directoryIterator = new \RecursiveDirectoryIterator($directory);
        if ($recursive == true) {
            $directoryIterator
                = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        }

        foreach ( $directoryIterator as $file) {
            if (!$this->isDot($file->getFilename())) {
                $filename = $file->getRealPath();
                array_push($files, $filename);
            }
        }

        return $files;
    }

    /**
     * Get files recursively.
     *
     * @param $directory
     * @return array
     */
    protected function allFiles($directory)
    {
        return $this->files($directory, true);
    }

    /**
     * Get the absolute path.
     *
     * @param $path
     * @return string
     */
    protected function getRealPath($path)
    {
        return $this->rootPath . $path;
    }

    /**
     * Determine the filename is . or ..
     *
     * @param $string
     * @return bool
     */
    protected function isDot($string)
    {
        if ($string == '.' || $string =='..')
            return true;

        return false;
    }

    /**
     * Get relative path.
     *
     * @param $path
     * @return mixed
     */
    protected function getRelativePath($path)
    {
        return str_replace($this->rootPath, '', $path);
    }

}
