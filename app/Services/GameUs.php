<?php

namespace App\Services;

use App\Contracts\GameUs as GameUsContract;
use App\Models\Batch as BatchModel;
use App\Models\GameUs as GameUsModel;
use App\Models\PriceUs as PriceUsModel;
use Illuminate\Support\Facades\Log;

define('US_ALGOLIA_ID',  'U3B6GR4UA3');
define('US_ALGOLIA_KEY', 'c4da8be7fd29f0f5bfa42920b0a99dc7');
define('US_QUERY_URL',   'https://'.US_ALGOLIA_ID.'-dsn.algolia.net/1/indexes/*/queries');

class GameUs implements GameUsContract
{
    private $num_per_page = 40;
    private $output = null;

    public function __get($name)
    {
        if ($name == 'batch_id') {
            $batch = new BatchModel();
            $batch->Country   = 'us';
            $batch->StartTime = date('Y-m-d H:i:s');
            $batch->save();
            $this->batch_id = $batch->getKey();
            return $this->batch_id;
        }
    }

    public function __destruct()
    {
        if (isset($this->batch_id)) {
            $batch = BatchModel::firstWhere('ID', $this->batch_id);
            $batch->EndTime = date('Y-m-d H:i:s');
            $batch->save();
        }
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getGamePrice()
    {
        $header = $this->getHeader();
        for ($page = 0; ;++$page) {
            $query   = $this->getQueryParam($page);
            $data    = $this->getGamesData($header, $query);
            $total   = $this->getTotalGamesNum($data);
            $is_save = $this->saveGamesData($data);
            $this->progressBar(ceil($total/$this->num_per_page));
            if (!$is_save) { break; }
        }
    }

    private function progressBar($sliceNum = 0)
    {
        static $bar, $count;
        if (!isset($this->output)) { return false; }
        if (isset($sliceNum) && !isset($bar)) {
            $bar = $this->output->createProgressBar($sliceNum);
            $bar->start();
        }
        if (isset($bar)) {
            $bar->advance();
            $count = isset($count)? $count+1 : 1;
        }
        if ($count == $sliceNum) {
            $bar->finish();
        }
    }

    private function getQueryParam($page = 0)
    {
        $facets = [
            "generalFilters",
            "platform",
            "availability",
            "genres",
            "howToShop",
            "virtualConsole",
            "franchises",
            "priceRange",
            "esrbRating",
            "playerFilters"
        ];

        $param = new \stdClass();
        $param->indexName = 'ncom_game_en_us_price_asc';
        $param->params    = http_build_query([
            'query'             => '',
            'hitsPerPage'       => $this->num_per_page,
            'maxValuesPerFacet' => 30,
            'page'              => $page,
            'analytics'         => 'false',
            'facets'            => json_encode($facets),
            'tagFilters'        => '',
            'facetFilters'      => '[["generalFilters:Deals"],["platform:Nintendo Switch"]]'
        ]);

        $request = new \stdClass();
        $request->requests = [$param];
        return json_encode($request);
    }

    private function getHeader()
    {
        return [
            "cache-control: no-cache",
            "content-type: application/json",
            "x-algolia-api-key: ".US_ALGOLIA_KEY,
            "x-algolia-application-id: ".US_ALGOLIA_ID
        ];
    }

    private function getGamesData($header, $query)
    {
        for ($req_index = 0; $req_index < 3; ++$req_index) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => US_QUERY_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_POSTFIELDS     => $query
            ]);
            $response = curl_exec($curl);
            $error    = curl_error($curl);
            curl_close($curl);

            if (!$error) {
                $response_array = json_decode($response, true);
                if (isset($response_array['results'][0])) {
                    $result = $response_array['results'][0];
                    return $response_array;
                } elseif($req_index === ($req_index - 1)) {
                    Log::error($response);
                }
            }
        }
    }

    private function getTotalGamesNum(Array $response)
    {
        $game_num = $response['results'][0]['nbHits'] ?? 0;
        return $game_num;
    }

    private function saveGamesData(Array $response)
    {
        $result = $response['results'][0]['hits'] ?? [];
        if (empty($result)) { return false; }

        $game  = new GameUsModel();
        $price_data   = [];
        $de_duplicate = [];
        foreach ($result as $row) {
            if (isset($row['title']) && isset($de_duplicate[ $row['title'] ])) {
                continue;
            }
            $game_data = [
                'Title'        => $row['title'] ?? '',
                'URL'          => $row['url'] ?? '',
                'NSUID'        => $row['nsuid'] ?? '',
                'Boxart'       => $row['boxart'] ?? '',
                'ReleaseDate'  => $row['releaseDateDisplay'] ?? '',
                'NumOfPlayers' => $row['numOfPlayers'] ?? '',
                'Genres'       => $row['genres'] ?? [],
                'Publishers'   => $row['publishers'] ?? [],
                'NSO'          => $row['generalFilters'] ?? [],
                'MSRP'         => $row['msrp'] ?? '0.0',
                'LowestPrice'  => $row['lowestPrice'] ?? '0.0',
                'PriceRange'   => $row['priceRange'] ?? '',
                'Availability' => $row['availability'] ?? '',
                'ObjectID'     => $row['objectID'] ?? '',
                'Description'  => $row['description'] ?? '',
                'Player1'      => $row['playerFilters'] ?? [],
                'Player2'      => $row['playerFilters'] ?? [],
                'Player3'      => $row['playerFilters'] ?? [],
                'Player4'      => $row['playerFilters'] ?? []
            ];
            $game->insertOrUpdate($game_data);
            $curr = $game::firstWhere('Title', $row['title']);
            $de_duplicate[ $row['title'] ] = true;

            if (empty($curr->ID)) { continue; }
            $price_data[] = [
                'BatchID' => $this->batch_id,
                'GameID'  => $curr->ID,
                'Price'   => $row['salePrice']
            ];
        }
        PriceUsModel::insert($price_data);
        unset($price_data, $de_duplicate);
        return true;
    }
}