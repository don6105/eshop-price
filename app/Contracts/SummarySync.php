<?php

namespace App\Contracts;

interface SummarySync
{
    public function syncSummaryInfo($country);
}