<?php

namespace App\Listeners;

use App\Eevents\FileMove;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FileMoved
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
     * @param  FileMove  $event
     * @return void
     */
    public function handle(FileMove $event)
    {
        //
    }
}
