<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExchangePull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull exchange rate and update to database';

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
        $Exchange = app()->make('ExchangePull');
        $Exchange->getExchangeRate();
        return 0;
    }
}
