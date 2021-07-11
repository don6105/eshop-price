<?php

namespace App\Contracts;

interface SummarySync
{
    public function syncGameInfo($country);
}