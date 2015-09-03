<?php

namespace TicketBot;

class JiraRemoteLink
{
    public $url;

    public $title;

    public $summary;

    public function __construct($url, $title, $summary = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->summary = $summary;
    }
}
