<?php

namespace App\Contracts;

interface Summary
{
    public function syncGameInfo($country);
    public function setGameGroup();
}