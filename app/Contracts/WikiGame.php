<?php

namespace App\Contracts;

interface WikiGame
{
    public function getGameList():Array;
    public function saveGameGroup(Array $gameList):Int;
}