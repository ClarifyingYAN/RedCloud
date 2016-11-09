<?php

namespace App\Listeners;

use App\Events\FileCreate;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
     * @return void
     */
    public function handle(FileCreate $event)
    {
        //
    }
}
