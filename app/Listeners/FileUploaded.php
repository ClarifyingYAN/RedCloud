<?php

namespace App\Listeners;

use App\Events\FileUpload;
use App\Http\Controllers\FileController;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FileUploaded
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
     * @param  FileUpload  $event
     * @return void
     */
    public function handle(FileUpload $event)
    {
        $this->upload($event->filename, $event->tmp, $event->destination);
    }

    public function upload($filename, $tmp, $destination)
    {
        $file = new FileController();
        $file->upload($filename,$tmp,$destination);
    }
}
