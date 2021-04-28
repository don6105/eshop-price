<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Game extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:crawl {country}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'game:crawl {country: us}';

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
        print_r($this->argument());

        // $slice_num = 10;
        // $bar = $this->output->createProgressBar($slice_num);
        // $bar->start();
        // for($i = 0; $i < $slice_num; ++$i) {
        //     sleep(1);   // 模拟执行耗时任务
        //     $bar->advance();
        // } 
        // $bar->finish();
        // echo PHP_EOL;
        return 0;
    }
}
