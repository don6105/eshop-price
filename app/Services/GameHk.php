<?php

namespace App\Services;
use Illuminate\Support\Arr;
use App\Contracts\Game as GameContract;
use App\Services\Base as BaseService;
use App\Models\Batch as BatchModel;
use App\Models\GameHk as GameHkModel;
use App\Models\PriceHk as PriceHkModel;
use App\Libraries\Curl as CurlLib;
use voku\helper\HtmlDomParser;

define('HK_PAGE_URL', 'https://store.nintendo.com.hk/download-code?product_list_dir=asc&product_list_limit=48');

class GameHk extends BaseService implements GameContract
{
    public function __get($name)
    {
        if ($name == 'batch_id') {
            $batch = new BatchModel();
            $batch->Country   = 'hk';
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
        $Curl      = new CurlLib();
        $next_page = HK_PAGE_URL;
        do {
            echo $next_page.PHP_EOL;
            $response = $Curl->run($next_page);
            list($games, $next_page) = $this->parseGamePricePage($response);
            $this->saveGamesData($games); 
        } while($next_page !== null);
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
                $game_price = $this->getHistoryPrice($row->ID);
                $game_info  = array_merge(
                    $game_info, 
                    $game_image, 
                    $game_video,
                    $game_price,
                    ['Sync' => 0],
                    ['UpdateInfoTime' => date('Y-m-d H:i:s')]  
                );
                GameHkModel::where('ID', $row->ID)->update($game_info);
                $this->progressBar($total_num);
            }
        }
    }



    private function parseGamePricePage($response)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom = HtmlDomParser::str_get_html($response['content']);

        $pages = $dom->find('ul.pages-items > .pages-item-next');
        if (count($pages) == 0) {
            $next_page = null;
        } else {
            $next_page = html_entity_decode( $pages->findOne('a')->getAttribute('href') );
        }
        //var_dump($pages); exit;

        $targets = $dom->find('#amasty-shopby-product-list li.product-item');
        $games   = [];
        foreach ($targets as $t) {
            $url         = $t->findOne('a')->getAttribute('href');
            $boxart      = $t->findOne('img')->getAttribute('src');
            $title       = $t->findOne('.product-item-name')->text();
            $price       = $t->findOne('.price-final_price span.price')->innerText();
            $price       = floatval(trim(str_replace('HKD', '', $price)));
            $release_day = $t->findOne('.category-product-item-released')->innerText();
            $release_day = trim(str_replace('<span>發售日期</span>', '', $release_day));
            $games[] = [
                'URL'         => $url,
                'Boxart'      => $boxart,
                'Title'       => html_entity_decode($title, ENT_QUOTES),
                'Price'       => $price,
                'ReleaseDate' => $release_day
            ];
            //var_dump($games); exit;
        }
        return array($games, $next_page);
    }

    private function saveGamesData($games)
    {
        if (empty($games) || !is_array($games)) {
            return false;
        }

        $GameHk     = new GameHkModel();
        $price_data = [];
        foreach ($games as $game) {
            $price = $game['Price'];
            $game['Description'] = '';
            $game['Sync']        = 0;
            $game['UpdateTime']  = date('Y-m-d H:i:s');
            unset($game['Price']);
            $GameHk->insertOrUpdate($game, Arr::except($game, ['Description']));

            $curr = $GameHk::firstWhere('Title', $game['Title']);
            if (empty($curr->ID)) { continue; }
            $price_data[] = [
                'BatchID' => $this->batch_id,
                'GameID'  => $curr->ID,
                'Price'   => $price
            ];
        }
        PriceHkModel::insert($price_data);
        unset($price_data);
        return true;
    }

    private function getTodoGameInfo($getNum = false)
    {
        $last_check = date('Y-m-d H:i:s', strtotime('-8 hours'));
        $orm = GameHkModel::where('UpdateInfoTime', '<', $last_check)
                ->orWhereNull('UpdateInfoTime');
        //$orm = GameHkModel::where('ID', '2');
        
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

        $dom        = HtmlDomParser::str_get_html($response['content']);
        $game_size  = $dom->findOne('.required_space .attribute-item-val')->innerText();
        $desc       = $dom->findOne('.mfr_description .product-attribute-content')->innerHtml();
        $player_num = $dom->findOne('.no_of_players > .product-attribute-val')->innerText();
        $player_num = preg_replace('/[^0-9~]+/im', '', $player_num);
        $genres     = $dom->findOne('.game_category .attribute-item-val')->innerText();
        $publisher  = $dom->findOne('.publisher .attribute-item-val')->innerText();
        $nso        = count($dom->find('.no_of_players_online'))>0? 'Yes' : 'No';
        $langs      = $dom->findOne('.supported_languages .attribute-item-val')->innerText(); 
        $langs      = explode(',', $langs);
        $playmode   = $dom->findOne('.supported_play_modes');
        //-------------------------------------------------------------------
        $info       = [
            'Description'     => html_entity_decode($desc, ENT_QUOTES),
            'GameSize'        => $game_size,
            'NumOfPlayers'    => $player_num,
            'Genres'          => $genres,
            'Publishers'      => $publisher,
            'NSO'             => $nso,
            'SupportEnglish'  => $this->findInArray('英文', $langs)? 1 : 0,
            'SupportChinese'  => $this->findInArray('中文', $langs)? 1 : 0,
            'SupportJapanese' => $this->findInArray('日文', $langs)? 1 : 0,
            'TVMode'          => isset($playmode->find('.tv_mode')[0])? 1 : 0,
            'TabletopMode'    => isset($playmode->find('.tabletop_mode')[0])? 1 : 0,
            'HandheldMode'    => isset($playmode->find('.handheld_mode')[0])? 1 : 0,
        ];
        //var_dump($info); exit;
        return $info;
    }

    private function parseGalleryImage($response)
    {
        if (empty($response['content'])) {
            return ['GalleryImage' => ''];
        }
        $dom  = HtmlDomParser::str_get_html($response['content']);
        $json = $dom->findOne('.product.media script[type="text/x-magento-init"]')->innerText();  
        $json = json_decode($json, true);
        if (!isset($json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'])) {
            return ['GalleryImage' => ''];
        }
        $json = $json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'];
        $json = array_filter($json, function($k) {
            return $k['type'] == 'image';
        });
        $json = array_column($json, 'img');
        //var_dump($json); exit;
        return ['GalleryImage' => implode(';;', $json)];
    }

    private function parseGalleryVideo($response)
    {
        if (empty($response['content'])) {
            return ['GalleryVideo' => ''];
        }
        $dom  = HtmlDomParser::str_get_html($response['content']);
        $json = $dom->findOne('.product.media script[type="text/x-magento-init"]')->innerText();  
        $json = json_decode($json, true);
        if (!isset($json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'])) {
            return ['GalleryVideo' => ''];
        }
        $json = $json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'];
        $json = array_filter($json, function($k) {
            return $k['type'] == 'video';
        });
        //var_dump($json); exit;
        if (count($json) > 0) {
           return ['GalleryVideo' => current( array_column($json, 'videoUrl') )]; 
       } else {
           return ['GalleryVideo' => ''];
       }
    }

    private function getHistoryPrice($gameID)
    {
        $price = [
            'MSRP'        => PriceHkModel::where('GameID', $gameID)->max('Price'),
            'LowestPrice' => PriceHkModel::where('GameID', $gameID)->min('Price')
        ];
        return $price;
    }

    private function findInArray($string, $array)
    {
        foreach ($array as $row) {
            if (stripos($row, $string) !== false) { return true; }
        }
        return false;
    }
}