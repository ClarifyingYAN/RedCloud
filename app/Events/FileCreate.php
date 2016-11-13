<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use App\Models\File;
use App\Models\User;

class FileCreate extends Event
{
    use SerializesModels;

    public $file;
    
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $file)
    {
        $this->file = $file;
        $this->user = $user;
    }

}
