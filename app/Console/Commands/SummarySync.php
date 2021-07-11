<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SummarySync extends Command
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
        $summary_name = '\\App\Models\\Game'.ucfirst($country);
        if (!empty($country) && class_exists($summary_name)) {
            echo "Start summary:sync {$country} @ ".date('Y-m-d H:i:s').PHP_EOL;

            $summary = app()->make('SummarySync');
            if (!$this->option('schedule')) {
                $summary->setOutput($this->output);
            }
            $sync_num = $summary->syncGameInfo($country);
            $this->info(PHP_EOL." summary({$country}) finished.");

            echo "End summary:sync {$country} @ ".date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;

            $this->callBack($sync_num);
        }
        return 0;
    }


    
    private function callBack($syncNum)
    {
        if ($syncNum > 0) {
            $this->call('summary:group', [
                '--schedule' => $this->option('schedule')? true : false
            ]);    
        }
    }
}
