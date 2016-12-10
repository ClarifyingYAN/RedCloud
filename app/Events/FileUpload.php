<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FileUpload extends Event
{
    use SerializesModels;

    public $filename;

    public $tmp;

    public $destination;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($filename, $tmp, $destination)
    {
        $this->filename = $filename;
        $this->tmp = $tmp;
        $this->destination = $destination;
    }

}
