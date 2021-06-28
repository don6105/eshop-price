<?php

namespace App\Console\Commands;

use App;
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
        $crawler = App::make('Game', ['country' => $country]);

        echo "Start crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL;
        if (isset($crawler)) {
            if (!$this->option('schedule')) {
                $crawler->setOutput($this->output);
            }
            # get main info and price.
            $crawler->getGamePrice();
            $this->info(PHP_EOL."  game({$country}) crawler finished.");
            # get extend info(gallery, languages, gamesize).
            $crawler->getGameInfo();
            $this->info(PHP_EOL."  game_ext({$country}) crawler finished.");
        } else {
            echo 'Service not found.'.PHP_EOL;
        }
        echo "End crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;

        return 0;
    }
}
