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
    protected $signature = 'summary:price {--schedule}';

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
        $summary = app()->make('SummaryPrice');
        if (!$this->option('schedule')) {
            $summary->setOutput($this->output);
        }
        $summary->setSummaryPrice();
        return 0;
    }
}
