<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\FileController;
use App\Http\Requests;

class CloudStorageController extends FileController
{
    public function index()
    {
        echo 'test';
    }
}
