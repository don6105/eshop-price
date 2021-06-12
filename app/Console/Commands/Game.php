<?php

namespace App\Console\Commands;

use App;
use Illuminate\Console\Command;

class Game extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:crawl {country?} {--schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'game:crawl {country: us} {--schedule}';

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
        $crawler_name = 'Game'.ucfirst($country);
        if (!empty($country) && app()->bound($crawler_name)) {
            echo "Start crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL;
            
            $crawler = App::make($crawler_name);
            if (!$this->option('schedule')) {
                $crawler->setOutput($this->output);
            }

            # get main info and price.
            $crawler->getGamePrice();
            $this->info(PHP_EOL."  game({$country}) crawler finished.");

            # get extend info(gallery, languages, gamesize).
            $crawler->getGameInfo();
            $this->info(PHP_EOL."  game_ext({$country}) crawler finished.");

            echo "End crawl game({$country}) @ ".date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;
        }
        
        // $game_list = app('Translate')->getGameNameList();
        // print_r($this->argument());
        // print_r($this->options());
        return 0;
    }
}
