<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FileMove extends Event
{
    use SerializesModels;
    
    public $user;
    
    public $from;
    
    public $to;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $from, $to)
    {
        $this->user = $user;
        $this->from = $from;
        $this->to = $to;
    }
}
