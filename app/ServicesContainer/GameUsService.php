<?php

namespace App\ServicesContainer;

use App\Contracts\GameUsContract;
use App\Models\Game;
use Illuminate\Support\Facades\Log;

define('US_ALGOLIA_ID',  'U3B6GR4UA3');
define('US_ALGOLIA_KEY', 'c4da8be7fd29f0f5bfa42920b0a99dc7');
define('US_QUERY_URL',   'https://'.US_ALGOLIA_ID.'-dsn.algolia.net/1/indexes/*/queries');

class GameUsService implements GameUsContract
{
    public function getGamePrice()
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
                CURLOPT_POSTFIELDS     => $this->getQueryParam(),
                CURLOPT_HTTPHEADER     => $this->getHeader()
            ]);
            $response = curl_exec($curl);
            $error    = curl_error($curl);
            curl_close($curl);

            if (!$error) {
                $response_array = json_decode($response, true);
                if (isset($response_array['results'][0])) {
                    $result = $response_array['results'][0];
                    printf('%d x %d >= %d'.PHP_EOL, $result['nbPages'], $result['hitsPerPage'], $result['nbHits']);
                    $this->saveGamesData($response_array);
                    break;
                } elseif($req_index === ($req_index - 1)) {
                    Log::error($response);
                }
            }
        }
    }

    private function getQueryParam($page = 0, $numPerPage = 40)
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
            'hitsPerPage'       => $numPerPage,
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

    private function getTotalGameNum(Array $response)
    {
        $game_num = $response['results'][0]['nbHits'] ?? 0;
        return $game_num;
    }

    private function saveGamesData(Array $response)
    {
        $result = $response['results'][0]['hits'] ?? [];
        if (empty($result)) { return false; }
        foreach ($result as $row) {
            $game = new Game;
            $game->Title        = $row['title'];
            $game->URL          = $row['url'];
            $game->NSUID        = $row['nsuid'];
            $game->Boxart       = $row['boxart'];
            $game->ReleaseDate  = $row['releaseDateDisplay'];
            $game->NumOfPlayers = $row['numOfPlayers'];
            $game->Genres       = $row['genres'];
            $game->Publishers   = $row['publishers'];
            $game->NSO          = $row['generalFilters'];
            $game->MSRP         = $row['msrp'];
            $game->LowestPrice  = $row['lowestPrice'];
            $game->PriceRange   = $row['priceRange'];
            $game->Availability = $row['availability'];
            $game->ObjectID     = $row['objectID'];
            $game->Description  = $row['description'];
            $game->Player1      = $row['playerFilters'];
            $game->Player2      = $row['playerFilters'];
            $game->Player3      = $row['playerFilters'];
            $game->Player4      = $row['playerFilters'];
            $game->save();
        }
    }
}