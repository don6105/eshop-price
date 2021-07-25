<?php

namespace App\Services\GameCrawler\Interfaces;

interface Parser {
    public function initDom(String $content):Void;
    public function getData():Array;
}