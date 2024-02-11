<?php

namespace ByteBlitz\Notify;

use ByteBlitz\Notify\Service\Notify as NotifySerice;

class Notify {


    public function __call($method, $parameters)
    {
        $template = new NotifySerice();

        return $template->$method(...$parameters);
    }
}
