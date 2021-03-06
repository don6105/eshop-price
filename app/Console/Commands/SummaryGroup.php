<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SummaryGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:group {--schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make same games into the group from each eshop of country.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $summary   = app()->make('SummaryGroup');
        $group_num = $summary->setSummaryGroup();
        $this->callBack($group_num);
        return 0;
    }

    private function callBack($groupNum)
    {
        if ($groupNum > 0) {
            $this->call('summary:price');    
        }
    }
}
