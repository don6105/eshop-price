<?php

namespace App\Services;

use App\Contracts\GameUs as GameUsContract;
use App\Services\Base as BaseService;
use App\Models\Batch as BatchModel;
use App\Models\GameUs as GameUsModel;
use App\Models\PriceUs as PriceUsModel;
use App\Libraries\Curl as CurlLib;
use Illuminate\Support\Facades\Log;
use voku\helper\HtmlDomParser;

define('US_ALGOLIA_ID',  'U3B6GR4UA3');
define('US_ALGOLIA_KEY', 'c4da8be7fd29f0f5bfa42920b0a99dc7');
define('US_QUERY_URL',   'https://'.US_ALGOLIA_ID.'-dsn.algolia.net/1/indexes/*/queries');

class GameUs extends BaseService implements GameUsContract
{
    private $num_per_page = 40;


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

    public function getGameInfo()
    {
        $Curl      = new CurlLib();
        $total_num = $this->getTodoGameInfo(true);
        while(count($todo_data = $this->getTodoGameInfo()) > 0) {
            foreach ($todo_data as $row) {
                $response   = $Curl->run($row->URL);
                $game_info  = $this->parseGameInfoPage($response);
                $game_image = $this->parseGalleryImage($response);
                $game_video = $this->parseGalleryVideo($response);
                $game_info  = array_merge(
                    $game_info, 
                    $game_image, 
                    $game_video,
                    ['UpdateInfoTime' => date('Y-m-d H:i:s')]
                );
                GameUsModel::where('ID', $row->ID)->update($game_info);
                $this->progressBar($total_num);
            }
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
        return null;
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

        $GameUs       = new GameUsModel();
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
                'Player4'      => $row['playerFilters'] ?? [],
                'Sync'         => 0,
                'UpdateTime'   => date('Y-m-d H:i:s')
            ];
            $GameUs->insertOrUpdate($game_data);
            $curr = $GameUs::firstWhere('Title', $row['title']);
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

    private function getTodoGameInfo($getNum = false)
    {
        $last_week = date('Y-m-d H:i:s', strtotime('-7 days'));
        $orm = GameUsModel::where('UpdateInfoTime', '<', $last_week)
            ->orWhereNull('UpdateInfoTime');
        
        if(!$getNum) {
            $batch_size = 500;
            $r = $orm->take($batch_size)->get();
        } else {
            $r = $orm->count();
        }
        return $r;
    }

    private function parseGameInfoPage($response)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom       = HtmlDomParser::str_get_html($response['content']);
        $languages = $dom->findOne('.supported-languages dd')->innertext;
        $languages = array_map('trim', explode(',', $languages));
        $languages = array_map('strtolower', $languages);
        $info      = [
            'GameSize'        => $dom->findOne('.file-size dd')->innertext,
            'SupportEnglish'  => in_array('english', $languages)?  1 : 0,
            'SupportChinese'  => in_array('chinese', $languages)?  1 : 0,
            'SupportJapanese' => in_array('japanese', $languages)? 1 : 0
        ];

        $playmode = [
            '.playmode-tv'       => 'TVMode',
            '.playmode-tabletop' => 'TabletopMode',
            '.playmode-handheld' => 'HandheldMode'
        ];
        foreach ($playmode as $mode => $db_colum) {
            $alt = $dom->findOne($mode.' img')->alt;
            $info[$db_colum] = stripos($alt, 'not supported') === false ? 1 : 0; 
        }
        return $info;
    }

    private function parseGalleryVideo($response)
    {
        if (empty($response['content'])) {
            return ['GalleryVideo' => ''];
        }

        $Curl = new CurlLib();
        $dom  = HtmlDomParser::str_get_html($response['content']);
        $url  = 'https://assets.nintendo.com/video/upload/sp_vp9_full_hd/v1/';
        $url .= $dom->findOne('product-gallery [type="video"]')->getAttribute('video-id');
        $url .= '.mpd';
        $response = $Curl->run($url);
        if (empty($response['content'])) {
            ['GalleryVideo' => ''];
        }

        $pattern  = '/<baseurl>([^\n]+)<\/baseurl>/i';
        $base_url = 'https://assets.nintendo.com';
        $videos   = [];
        if (preg_match_all($pattern, $response['content'], $m) > 0) {
            $videos = array_filter($m[1], function($v) {
                return stripos($v, 'h_720');
            });
            $videos = array_map(function($u) use ($base_url) {
                return $base_url.$u;
            }, $videos);
        }
        return ['GalleryVideo' => empty($videos) ? '' : implode(';;', $videos)];
    }

    private function parseGalleryImage($response)
    {
        if (empty($response['content'])) {
            return ['GalleryImage' => ''];
        }

        $dom    = HtmlDomParser::str_get_html($response['content']);
        $images = $dom->find('product-gallery [type="image"]')->src;
        return ['GalleryImage' => empty($images) ? '' : implode(';;', $images)];
    }
}