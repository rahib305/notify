<?php

namespace ByteBlitz\Notify\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class Notify extends Facade implements ShouldQueue{

    protected static function getFacadeAccessor()
    {
        return 'notify';
    }
}
