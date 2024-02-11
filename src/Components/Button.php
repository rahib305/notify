<?php

namespace ByteBlitz\Notify\Components;

class Button {

    protected $url;

    protected $btnText;

    public function __construct($url, $btnText)
    {
        $this->url = $url;
        $this->btnText = $btnText;
    }

    public function success() {
        return '<a href="'.$this->url.'" class="btn btn-primary">'.$this->btnText.'</a>';
    }
}
