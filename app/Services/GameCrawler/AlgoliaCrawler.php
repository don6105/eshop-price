<?php

namespace App\Services\GameCrawler;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Models\Batch as BatchModel;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;

class AlgoliaCrawler {
    private $num_per_page = 100;
    private $total_num    = [];
    private $done_num     = [];

    public function __construct($gameModel, $priceModel)
    {
        $this->Game    = $gameModel;
        $this->Price   = $priceModel;
        $this->Config  = new Config($this->country);
        $this->Query   = new AlgoliaQuery($this->Config);
        $this->Client  = new Client([
            'handler'  => $this->getGuzzleHandler(),
            'headers'  => $this->Query->getQueryHeaders(),
            'base_uri' => $this->Query->getQueryUrl()
        ]);

        $this->initClassVar();
    }

    public function __get($name)
    {
        if (strcasecmp($name, 'batch_id') === 0) {
            $batch = new BatchModel();
            $batch->Country   = $this->country;
            $batch->StartTime = date('Y-m-d H:i:s');
            $batch->save();
            $this->batch_id = $batch->getKey();
            return $this->batch_id;
        } elseif (strcasecmp($name, 'country') === 0) {
            $this->country = $this->getCountry();
            return $this->country;
        }
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getGameData()
    {
        $orders = $this->Config->getConfig('base.order');
        $ranges = $this->Config->getConfig('base.range');
        if (!isset($orders, $ranges)) {
            dd('Please check order1 and order2 in '.$this->Config->getConfigPath());
        }

        while (true) {
            $promises  = $this->getBatchClient($ranges);
            if (empty($promises)) { break; }
            $responses = Promise\Utils::settle($promises)->wait();
            $this->getBatchResult($responses);
        }
        $this->closeProgressBar();
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

    private function initClassVar()
    {
        $ranges = $this->Config->getConfig('base.range', []);
        $this->done_num  = array_fill_keys($ranges, 0);
        $this->total_num = $this->done_num;
    }

    private function getBatchClient(Array $ranges):Array
    {
        $promises = [];
        foreach ($ranges as $range) {
            $params = $this->prepareQueryParams($range);
            if (empty($params)) { continue; }
            $promises[$range] = $this->Client->postAsync('', ['body' => $params]);
        }
        return $promises;
    }

    private function getBatchResult(Array $responses)
    {
        $algolia_arr = [];
        foreach($responses as $range => $response) {
            $algolia             = new AlgoliaResponse($response);
            $algolia_arr[$range] = $algolia->game_data;
            $this->setTotalNum($range, $algolia->total_num);
        }
        $this->initProgressBar();
        foreach ($algolia_arr as $range => $algolia) {
            $this->done_num[$range] += $this->saveGameData($algolia);
            $this->increaseProgressBar();
        }
    }
    
    private function prepareQueryParams(String $range):String
    {
        static $page1, $page2;
        if (!isset($page1[$range])) {
            $page1[$range] = 0;
        }
        if (!isset($page2[$range])) {
            $page2[$range] = 0;
        }
        if ($this->total_num[$range] > 0 && 
            $this->total_num[$range] <= 1000 &&
            $this->done_num[$range] >= $this->total_num[$range]) {
            return '';
        }
        if ($this->done_num[$range] >= 2000) {
            return '';
        }
        $done_num = $this->done_num[$range]?? 0;
        $orders   = $this->Config->getConfig('base.order');
        $order    = ($done_num < 1000)? reset($orders) : end($orders);
        $p        = ($done_num < 1000)? $page1[$range]++ : $page2[$range]++;
        $num      = $this->num_per_page;
        $params   = $this->Query->getQueryParams($range, $order, $p, $num);
        return $params;
    }

    private function initProgressBar()
    {
        if (isset($this->output) && !isset($this->bar)) {
            $num_per_page = $this->num_per_page;
            $max_num = array_reduce(
                $this->total_num, 
                function ($total, $n) use ($num_per_page) {
                    $total += ($n > 1000)? 20 : ceil($n / $num_per_page);
                    return $total;
                },
                0
            );
            $this->bar = $this->output->createProgressBar($max_num);
            $this->bar->setFormat(" %current:4s% / %max:4s% [%bar%] %percent:3s%%\n");
            $this->bar->start();
        }
    }

    private function increaseProgressBar()
    {
        isset($this->bar) && $this->bar->advance(1);
    }

    private function closeProgressBar()
    {
        isset($this->bar) && $this->bar->finish();
    }

    private function setTotalNum(String $range, Int $num)
    {
        if (empty($this->total_num[$range])) {
            $this->total_num[$range] = $num;
        }
    }

    private function saveGameData(Array $gameData):Int
    {
        $save_num   = 0;
        $price_data = [];
        foreach ($gameData as $data) {
            $price = $data['Price'];
            unset($data['Price']);
            $data['Sync']       = 0;
            $data['UpdateTime'] = date('Y-m-d H:i:s');
            $this->Game->insertOrUpdate($data, Arr::except($data, ['Description']));
            ++$save_num;

            $curr = $this->Game->select('ID')->firstWhere('Title', $data['Title']);
            if (empty($curr->ID)) { continue; }
            $price_data[] = [
                'BatchID' => $this->batch_id,
                'GameID'  => $curr->ID,
                'Price'   => $price
            ];
        }
        $this->Price->insert($price_data);
        unset($price_data);
        return $save_num;
    }
}