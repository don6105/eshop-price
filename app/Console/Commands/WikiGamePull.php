<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WikiGamePull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikigame:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull wiki game list and update to database';

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
        $WikiGame = app()->make('WikiGamePull');
        $game_list = $WikiGame->getGameList();
        $WikiGame->saveGameGroup($game_list);
        return 0;
    }
}
