<?php

namespace App\Services;

use App\Contracts\Base as BaseContract;

class Base implements BaseContract {
    private $output = null;

    public function setOutput($output)
    {
        $this->output = $output;
    }

    protected function progressBar($sliceNum = 0)
    {
        static $bar, $count, $slice_num;
        if (!isset($this->output)) { return false; }

        if (!isset($slice_num) || $slice_num !== $sliceNum) {
            $slice_num = $sliceNum;
            $bar = $this->output->createProgressBar($sliceNum);
            $bar->start();
        }
        if (isset($bar) && $count == $sliceNum) {
            $bar->finish();
            unset($bar, $count);
        }
        if (isset($bar)) {
            $bar->advance();
            $count = isset($count)? $count+1 : 1;
        }
    }
}