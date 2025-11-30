<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\TransactionStatusUpdated;
use App\Listeners\SendTransactionNotification;

class EventServiceProvider extends ServiceProvider
{

protected $listen = [
    \App\Events\TransactionStatusUpdated::class => [
        \App\Listeners\SendTransactionNotification::class,
    ],
];

    public function boot(): void
    {
        parent::boot();
    }
}