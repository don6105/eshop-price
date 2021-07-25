<?php

namespace App\Services\GameCrawler;

use Illuminate\Support\Facades\Log;
use App\Models\Batch as BatchModel;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;

class GameExtCrawler {
    private $guzzle_parallel_num = 20;

    public function __construct($gameModel, $priceModel, $parser)
    {
        $this->Game   = $gameModel;
        $this->Price  = $priceModel;
        $this->Parser = $parser;
        $this->Client = new Client([
            'handler' => $this->getGuzzleHandler()
        ]);
    }

    public function __destruct()
    {
        if (isset($this->batch_id)) {
            $batch = BatchModel::firstWhere('ID', $this->batch_id);
            $batch->EndTime = date('Y-m-d H:i:s');
            $batch->save();
        }
    }

    public function __get($name)
    {
        if (strcasecmp($name, 'batch_id') === 0) {
            $batch = new BatchModel();
            $batch->Country   = $this->getCountry();
            $batch->StartTime = date('Y-m-d H:i:s');
            $batch->save();
            $this->batch_id = $batch->getKey();
            return $this->batch_id;
        }
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function run():Int
    {
        $total_num  = $this->getTodoGames(true);
        $this->initProgressBar($total_num);

        $done_num   = 0;
        $todo_games = [];
        while (!empty($todo_games = $this->getTodoGames())) {
            $batch     = array_slice($todo_games, 0, $this->guzzle_parallel_num);
            $promises  = $this->getBatchClient($batch);
            $responses = Promise\Utils::settle($promises)->wait();
            $num  = $this->getBatchResult($responses, [$this, 'saveGamesData']);
            $this->increaseProgressBar($num);
            $done_num += $num;
        }
        $this->closeProgressBar();
        return $done_num;
    }



    private function getCountry()
    {
        $class = get_class($this->Game);
        if (preg_match('/\WGame([a-z]{2})$/i', $class, $m) > 0) {
            return strtolower($m[1]);
        }
        return '';
    }

    private function getGuzzleHandler()
    {
        $method = __METHOD__;
        $stack  = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 3,
            'retry_on_status'    => [404, 429, 503, 500],
            'on_retry_callback'  => function($attemptNumber, $delay, &$request, &$options, $response) use ($method) {
                if ($attemptNumber === 3) {
                    $log = [
                        'Trace Source: '.$method,
                        'Status Code : '.$response->getStatusCode(),
                        'Request URL : '.$request->getUri(),
                        'Request Body: '.urldecode($request->getBody())
                    ];
                    Log::error(PHP_EOL.implode(PHP_EOL, $log).PHP_EOL);
                }
            }
        ]));
        return $stack;
    }

    private function initProgressBar($totalNum)
    {
        if (isset($this->output) && !isset($this->bar)) {
            $this->bar = $this->output->createProgressBar($totalNum);
            $this->bar->setFormat(" %current:4s% / %max:4s% [%bar%] %percent:3s%%\n");
            $this->bar->start();
        }
    }

    private function increaseProgressBar($num)
    {
        isset($this->bar) && $this->bar->advance($num);
    }

    private function closeProgressBar()
    {
        isset($this->bar) && $this->bar->finish();
    }

    private function getTodoGames(Bool $getNum = false)
    {
        $last_week = date('Y-m-d H:i:s', strtotime('-3 days'));
        $orm = $this->Game->where('UpdateInfoTime', '<', $last_week)
                ->orWhereNull('UpdateInfoTime');
        if(!$getNum) {
            $batch_size = 500;
            $r = $orm->orderBy('ID')->take($batch_size)->get()->toArray();
        } else {
            $r = $orm->count();
        }
        return $r;
    }

    private function getBatchClient(Array $todoList):Array
    {
        $promises = [];
        foreach ($todoList as $game) {
            $promises[ $game['Title'] ] = $this->Client->getAsync($game['URL']);
        }
        return $promises;
    }

    private function getBatchResult(Array $responses, Callable $callBack = null):Int
    {
        $done_num = 0;
        foreach ($responses as $title => $response) {
            try {
                $content = $response['value']->getBody()->getContents();
                $this->Parser->initDom($content);
                $data = $this->Parser->getData();
            } catch(\Throwable $t) {
                $data = [
                    'Title'       => $title,
                    'Description' => ''
                ];
            }
            if (is_callable($callBack) ||
                call_user_func_array('method_exists', (Array)$callBack)) {
                call_user_func_array($callBack, [$data]);
            }
            ++$done_num;
        }
        return $done_num;
    }

    private function saveGamesData(Array $data = null)
    {
        if (empty($data)) { return false; }
        
        if (isset($data['Price'])) {
            $price = $data['Price'];
            unset($data['Price']);
        }
        $data['UpdateInfoTime'] = date('Y-m-d H:i:s');
        $this->Game->insertOrUpdate($data);

        // save price
        if (isset($price)) {
            $curr = $this->Game->firstWhere('Title', $data['Title']);
            if(!empty($curr->ID)) {
                $price = [
                    'BatchID' => $this->batch_id,
                    'GameID'  => $curr->ID,
                    'Price'   => $price
                ];
                $this->Price->insert($price);
            }
        }
        return true;
    }
}