<?php

namespace App\Listeners;

use App\Events\FileCreate;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Controllers\FileController;

class FileCreated
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  FileCreate  $event
     * @return bool
     */
    public function handle(FileCreate $event)
    {
        return $this->makeDirectory($event->file);
    }
    
    protected function makeDirectory($path)
    {
        $file = new FileController();
        
        return $file->makeDirectory($path);
    }
}
