<?php

namespace App\Listeners;

use App\Events\FileDownload;
use App\Http\Controllers\FileController;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FileDownload
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
     * @param  FileDownload  $event
     * @return void
     */
    public function handle(FileDownload $event)
    {
        $this->download($event->file);
    }

    public function download($filename)
    {
        $file = new FileController();

        $file->download($filename);
    }
}
