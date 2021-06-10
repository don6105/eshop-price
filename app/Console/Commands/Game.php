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
        if ($this->argument('country') === 'us') {
            echo 'Start crawl game(us) @ '.date('Y-m-d H:i:s').PHP_EOL;

            $game_us = App::make('GameUs');
            if (!$this->option('schedule')) {
                $game_us->setOutput($this->output);
            }
            # get main info and price.
            $game_us->getGamePrice();
            $this->info(PHP_EOL.'  game(us) crawler finished!');
            # get extend info(gallery, languages, gamesize).
            $game_us->getGameInfo();
            $this->info(PHP_EOL.'  game_ext(us) crawler finished!');

            echo 'End crawl game(us) @ '.date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;
        } elseif($this->argument('country') === 'hk') {
            echo 'Start crawl game(hk) @ '.date('Y-m-d H:i:s').PHP_EOL;

            $game_hk = App::make('GameHk');
            if (!$this->option('schedule')) {
                $game_hk->setOutput($this->output);
            }
            # get main info and price.
            $game_hk->getGamePrice();
            $this->info(PHP_EOL.'  game(hk) crawler finished!');
            # get extend info(gallery, languages, gamesize).
            $game_hk->getGameInfo();
            $this->info(PHP_EOL.'  game_ext(hk) crawler finished!');

            echo 'End crawl game(hk) @ '.date('Y-m-d H:i:s').PHP_EOL.PHP_EOL;
        }

        
        // $game_list = app('Translate')->getGameNameList();
        // print_r($this->argument());
        // print_r($this->options());
        return 0;
    }
}
