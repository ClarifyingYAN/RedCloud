<?php

namespace App\Listeners;

use App\Events\FileMove;
use App\Http\Controllers\FileController;
use Cron\FieldFactory;
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
        $this->move($event->from, $event->to);
    }
    
    public function move($from, $to)
    {
        $file = new FileController();
        
        $file->move($from, $to);
    }
}
