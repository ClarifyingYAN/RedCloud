<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FileDelete extends Event
{
    use SerializesModels;

    public $user;

    public $file;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $file)
    {
        $this->user = $user;
        $this->file = $file;
    }
    
}
