<?php

namespace App\Console\Commands;

use App;
use Illuminate\Console\Command;

class Summary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:sync {country} {--schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'summary:sync {country} {--schedule}';

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
        $country      = strtolower($this->argument('country'));
        $summary_name = 'Game'.ucfirst($country);
        if (!empty($country) && app()->bound($summary_name)) {
            echo "Start summary({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL;

            $summary = App::make('Summary');
            if (!$this->option('schedule')) {
                $summary->setOutput($this->output);
            }
            $summary->getGameData($country);
            $this->info(PHP_EOL." summary({$country}) finished.");

            echo "End summary({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;
        }
        return 0;
    }
}
