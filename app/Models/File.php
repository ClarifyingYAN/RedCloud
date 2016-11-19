<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /**
     * file table.
     *
     * @var string
     */
    protected $table = 'filesinfo';

    protected $fillable = ['filename', 'type', 'path'];

    protected $visible = ['filename', 'type', 'path', 'basename', 'updated_at'];

}
