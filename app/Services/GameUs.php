<?php

namespace App\Services;

use App\Contracts\Game as GameContract;
use App\Services\Base as BaseService;
use App\Models\Batch as BatchModel;
use App\Models\GameUs as GameUsModel;
use App\Models\PriceUs as PriceUsModel;
use App\Libraries\Curl as CurlLib;
use voku\helper\HtmlDomParser;

define('US_ALGOLIA_ID',  'U3B6GR4UA3');
define('US_ALGOLIA_KEY', 'c4da8be7fd29f0f5bfa42920b0a99dc7');
define('US_QUERY_URL',   'https://'.US_ALGOLIA_ID.'-dsn.algolia.net/1/indexes/*/queries');

/*
Algolia限制只能抓一千筆資料，eshop(us)網站也是一樣。
you can only fetch the 1000 hits for this query. You can extend the number of hits returned via the paginationLimitedTo index parameter or use the browse method. You can read our FAQ for more details about browsing: https://www.algolia.com/doc/faq/index-configuration/how-can-i-retrieve-all-the-records-in-my-index 
*/

class GameUs extends BaseService implements GameContract
{
    private $num_per_page = 40;

    public function __get($name)
    {
        if ($name === 'batch_id') {
            $batch = new BatchModel();
            $batch->Country   = 'us';
            $batch->StartTime = date('Y-m-d H:i:s');
            $batch->save();
            $this->batch_id = $batch->getKey();
            return $this->batch_id;
        } elseif ($name === 'Curl') {
            $this->Curl = new CurlLib();
            return $this->Curl;
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
        $total  = null;
        for ($page = 0; ;++$page) {
            $query   = $this->getQueryParam($page);
            $data    = $this->getGamesData($header, $query);
            if (!isset($total)) {
                $total   = $this->getTotalGamesNum($data);
            }
            $data    = $this->parseGamePriceData($data);
            $is_save = $this->saveGamesData($data);
            $this->progressBar(ceil($total/$this->num_per_page));
            if (!$is_save) { break; }
        }
    }

    public function getGameInfo()
    {
        $this->Curl->setHeader(null);
        $total_num = $this->getTodoGameInfo(true);
        while(count($todo_data = $this->getTodoGameInfo()) > 0) {
            foreach ($todo_data as $row) {
                $response   = $this->Curl->run($row->URL);
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
        $param->indexName = 'ncom_game_en_us';
        $param->params    = http_build_query([
            'query'             => '',
            'hitsPerPage'       => $this->num_per_page,
            'maxValuesPerFacet' => 30,
            'page'              => $page,
            'analytics'         => 'false',
            'facets'            => json_encode($facets),
            'tagFilters'        => '',
            'facetFilters'      => '[["availability:Available now"],["platform:Nintendo Switch"]]'
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
        $this->Curl->setHeader($header);
        $response = $this->Curl->run(US_QUERY_URL, 30, $query);
        if (empty($response['content'])) {
            return null;
        }
        $result = json_decode($response['content'], true);
        return $result['results'][0] ?? null;
    }

    private function getTotalGamesNum(Array $data = null)
    {
        return $data['nbHits'] ?? 0;
    }

    private function parseGamePriceData(Array $data = null)
    {
        $result = $data['hits'] ?? [];
        if (empty($result)) { return null; }
        
        $game_data = [];
        foreach ($result as $row) {
            $game_data[] = [
                'Title'        => $row['title'] ?? '',
                'URL'          => $row['url'] ?? '',
                'NSUID'        => $row['nsuid'] ?? '',
                'Boxart'       => $row['horizontalHeaderImage'] ?? '',
                'ReleaseDate'  => $row['releaseDateDisplay'] ?? '',
                'NumOfPlayers' => $row['numOfPlayers'] ?? '',
                'Genres'       => $row['genres'] ?? [],
                'Publishers'   => $row['publishers'] ?? [],
                'NSO'          => $row['generalFilters'] ?? [],
                'MSRP'         => $row['msrp'] ?? '0.0',
                'LowestPrice'  => $row['lowestPrice'] ?? '0.0',
                'Description'  => '',
                'Sync'         => 0,
                'Price'        => $row['salePrice'] ?? 0.0,
                'UpdateTime'   => date('Y-m-d H:i:s')
            ];
        }
        return $game_data;
    }

    private function saveGamesData(Array $gameData = null)
    {
        if (empty($gameData)) { return false; }

        $GameUs     = new GameUsModel();
        $price_data = [];
        foreach ($gameData as $row) {
            $price = $row['Price'];
            unset($row['Price']);
            $GameUs->insertOrUpdate($row);

            $curr = $GameUs::firstWhere('Title', $row['Title']);
            if (empty($curr->ID)) { continue; }
            $price_data[] = [
                'BatchID' => $this->batch_id,
                'GameID'  => $curr->ID,
                'Price'   => $price
            ];
        }
        PriceUsModel::insert($price_data);
        unset($price_data);
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

    private function parseGameInfoPage(Array $response = null)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom        = HtmlDomParser::str_get_html($response['content']);
        $game_size  = $dom->findOne('.file-size dd')->innerText();
        $langs      = $dom->findOne('.supported-languages dd')->innerText();
        $langs      = array_map('trim', explode(',', $langs));
        $langs      = array_map('strtolower', $langs);
        $desc       = $dom->findOne('[itemprop="description"]')->innerHtml();
        $info       = [
            'GameSize'        => $game_size,
            'Description'     => $desc,
            'SupportEnglish'  => in_array('english',  $langs)? 1 : 0,
            'SupportChinese'  => in_array('chinese',  $langs)? 1 : 0,
            'SupportJapanese' => in_array('japanese', $langs)? 1 : 0
        ];

        $playmode = [
            '.playmode-tv'       => 'TVMode',
            '.playmode-tabletop' => 'TabletopMode',
            '.playmode-handheld' => 'HandheldMode'
        ];
        foreach ($playmode as $mode => $db_colum) {
            $alt = $dom->findOne($mode.' img')->getAttribute('alt');
            $info[$db_colum] = stripos($alt, 'not supported') === false ? 1 : 0; 
        }
        return $info;
    }

    private function parseGalleryVideo(Array $response = null)
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

    private function parseGalleryImage(Array $response = null)
    {
        if (empty($response['content'])) {
            return ['GalleryImage' => ''];
        }

        $dom    = HtmlDomParser::str_get_html($response['content']);
        $images = $dom->find('product-gallery [type="image"]')->src;
        return ['GalleryImage' => empty($images) ? '' : implode(';;', $images)];
    }
}