<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\FileCreate' => [
            'App\Listeners\FileCreated',
        ],
        'App\Events\FileDelete' => [
            'App\Listeners\FileDeleted',
        ],
        'App\Events\FileMove' => [
            'App\Listeners\FileMoved',
        ],
        'App\Events\FileUpload' => [
            'App\Listeners\FileUploaded',
        ],
        'App\Events\FileDownload' => [
            'App\Listeners\FileDownload',
        ]
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
