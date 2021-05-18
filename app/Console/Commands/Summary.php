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
        if ($this->argument('country') === 'us') {
            echo 'Start summary(us) @ '.date('Y-m-d H:i:s').PHP_EOL;

            $summary = App::make('Summary');
            if (!$this->option('schedule')) {
                $summary->setOutput($this->output);
            }
            $summary->getGameData($this->argument('country'));
            $this->info(PHP_EOL.'  summary(us) finished!');

            echo 'End summary(us) @ '.date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;
        }
        return 0;
    }
}
