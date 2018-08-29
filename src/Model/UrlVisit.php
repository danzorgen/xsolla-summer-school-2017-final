<?php

namespace UrlShortener\Model;

class UrlVisit
{
    public $id;

    public $url_id;

    public $visitedAt;

    public function __construct($id, $url_id, $visitedAt)
    {
        $this->id = $id;
        $this->url_id = $url_id;
        $this->visitedAt = $visitedAt;
    }
}