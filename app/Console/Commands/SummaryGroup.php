<?php

namespace App\Console\Commands;

use App;
use Illuminate\Console\Command;

class SummaryGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make same games into the group from each eshop of country.';

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
        $Summary = App::make('Summary');
        $Summary->setGameGroup();
        return 0;
    }
}