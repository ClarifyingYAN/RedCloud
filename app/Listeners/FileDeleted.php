<?php

namespace App\Listeners;

use App\Events\FileDelete;
use App\Http\Controllers\FileController;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FileDeleted
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
     * @param  FileDelete  $event
     * @return void
     */
    public function handle(FileDelete $event)
    {
        $this->delete($event->file);
    }
    
    public function delete($filename)
    {
        $file = new FileController();
        
        return $file->delete($filename);
    }
    
}
