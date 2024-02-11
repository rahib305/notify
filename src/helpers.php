<?php

use ByteBlitz\Notify\Components\Button;
use ByteBlitz\Notify\Service\Notify;

if(!function_exists('notify')) {
    function notify() : Notify {
        return app(Notify::class);
    }
}


if(!function_exists('notifyButton')) {
    function notifyButton($url, $btnText) : string {
        return '<a href="'.$url.'" class="btn btn-primary">'.$btnText.'</a>';
    }
}


if(!function_exists('notifyButton')) {
    function notifyButton($url, $btnText) : Button {
        return new Button($url, $btnText);
    }
}


if(!function_exists('notifyImage')) {
    function notifyImage($url) : string {
        return '<img src="'.$url.'" style="width:100%;"/>';
    }
}
