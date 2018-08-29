<?php

namespace UrlShortener\Model;

class Url
{
    public $id;

    public $user_id;

    public $full_url;

    public $hash;

    public $visits;

    public function __construct($id, $user_id, $full_url, $hash, $visits = 0)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->full_url = $full_url;
        $this->hash = $hash;
        $this->visits = $visits;
    }
}