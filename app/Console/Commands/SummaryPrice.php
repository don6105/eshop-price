<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SummaryPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:price {group_id? : restrict by provided group_id.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decide MinPrice and MinCountry in games of the group.';

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
        $group_id = intval($this->argument('group_id'));
        $summary  = app()->make('SummaryPrice');
        $summary->setSummaryPrice($group_id);
        return 0;
    }
}
