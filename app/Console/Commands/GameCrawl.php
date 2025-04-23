<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GameCrawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:crawl 
                            {country : country in 2 alphabet, ex: us, hk} 
                            {--schedule : disable progress bar in cronjob mode}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a crawler and get games from eshop';

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
        $country = strtolower($this->argument('country'));
        echo "Start crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL;
        
        $this->getInstance()
            ->setSchedule()
            ->getWorkList()
            ->runWorks();
        
        echo "End crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;

        $this->callBack($country);

        return 0;
    }

    

    private function getInstance()
    {
        $country       = strtolower($this->argument('country'));
        $this->crawler = app()->make('Game', ['country' => $country]);
        return $this;
    }

    private function setSchedule()
    {
        if (!empty($this->crawler) && !$this->option('schedule')) {
            $this->crawler->setOutput($this->output);
        }
        return $this;
    }

    private function getWorkList()
    {
        if (!empty($this->crawler)) {
            $interface    = class_implements($this->crawler);
            $methods      = get_class_methods(array_pop($interface));
            $finished_msg = array_map(function ($m) {
                return "  ".date('Y-m-d H:i:s')." $m() finished.".PHP_EOL;
            }, $methods);
            $this->work_list = array_combine($methods, $finished_msg);
        }
        return $this;
    }

    private function runWorks()
    {
        if (empty($this->crawler)) {
            echo 'Service not found.'.PHP_EOL;
        }
        if (!empty($this->work_list)) {
            foreach ((Array)$this->work_list as $work => $msg) {
                $this->crawler->$work();
                $this->info($msg);
            }
        }
    }

    private function callBack($country)
    {
        $this->call('summary:sync', [
            'country'    => $country,
            '--schedule' => $this->option('schedule')? true : false
        ]);
    }
}
