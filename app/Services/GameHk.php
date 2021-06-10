<?php

namespace App\Services;
use App\Contracts\Game as GameContract;
use App\Services\Base as BaseService;
use App\Models\Batch as BatchModel;
use App\Models\GameHk as GameHkModel;
use App\Models\PriceHk as PriceHkModel;
use App\Libraries\Curl as CurlLib;
use Illuminate\Support\Facades\Log;
use voku\helper\HtmlDomParser;

define('HK_PAGE_URL', 'https://store.nintendo.com.hk/games/all-released-games?product_list_order=release_date_asc');

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
        $Curl     = new CurlLib();
        $response = $Curl->run(HK_PAGE_URL);
        $games    = $this->parseGamePricePage($response);
        $this->saveGamesData($games);
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

        $dom     = HtmlDomParser::str_get_html($response['content']);
        $targets = $dom->find('.category-product-item');
        $games   = [];
        foreach ($targets as $t) {
            $url         = $t->findOne('a')->getAttribute('href');
            $boxart      = $t->findOne('img')->getAttribute('data-src');
            $title       = $t->findOne('.category-product-item-title-link')->innerText();
            $price       = $t->findOne('.category-product-item-price .price')->innerText();
            $price       = trim(str_replace('HKD', '', $price));
            $release_day = $t->findOne('.category-product-item-released')->innerText();
            $release_day = trim(str_replace('<b>發售日期</b>', '', $release_day));
            $games[] = [
                'URL'         => $url,
                'Boxart'      => $boxart,
                'Title'       => html_entity_decode($title, ENT_QUOTES),
                'Price'       => $price,
                'ReleaseDate' => $release_day
            ];
        }
        return $games;
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
            $game['UpdateTime']  = date('Y-m-d H:i:s');
            unset($game['Price']);
            $GameHk->insertOrUpdate($game);

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
        $last_week = date('Y-m-d H:i:s', strtotime('-7 days'));
        $orm = GameHkModel::where('UpdateInfoTime', '<', $last_week)
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

        $dom        = HtmlDomParser::str_get_html($response['content']);
        $game_size  = $dom->find('.required_space > div');
        $game_size  = isset($game_size[1])? $game_size[1]->innerText() : '';
        $desc       = $dom->findOne('.description .value')->innerHtml();
        $player_num = $dom->find('.no_of_players > div');
        $player_num = isset($player_num[1])? $player_num[1]->innerText() : '';
        $player_num = preg_replace('/\D+/im', '', $player_num);
        $player_num = is_numeric($player_num)? $player_num : -1;
        $genres     = $dom->find('.game_category > div');
        $genres     = isset($genres[1])? $genres[1]->innerText() : '';
        $publisher  = $dom->find('.publisher > div');
        $publisher  = isset($publisher[1])? $publisher[1]->innerText() : '';
        $nso        = $dom->findOne('.no_of_players_online')->innerText();
        $nso        = empty($nso)? 'Yes' : 'No';
        $langs      = $dom->find('.supported_languages > div');
        $langs      = isset($langs[1])? $langs[1]->innerText() : ''; 
        $langs      = array_map('trim', explode(',', $langs));
        $playmode   = $dom->find('.supported_play_modes > div');
        $playmode   = isset($playmode[1])? $playmode[1]->innerText() : '';
        $playmode   = array_map('trim', explode(',', $playmode));
        $info       = [
            'Description'     => html_entity_decode($desc, ENT_QUOTES),
            'GameSize'        => $game_size,
            'NumOfPlayers'    => $player_num,
            'Genres'          => $genres,
            'Publishers'      => $publisher,
            'NSO'             => $nso,
            'SupportEnglish'  => in_array('英文', $langs)? 1 : 0,
            'SupportChinese'  => in_array('中文', $langs)? 1 : 0,
            'SupportJapanese' => in_array('日文', $langs)? 1 : 0,
            'TVMode'          => in_array('TV',  $playmode)? 1 : 0,
            'TabletopMode'    => in_array('桌上', $playmode)? 1 : 0,
            'HandheldMode'    => in_array('手提', $playmode)? 1 : 0,
        ];
        return $info;
    }

    private function parseGalleryImage($response)
    {
        if (empty($response['content'])) {
            return ['GalleryImage' => ''];
        }
        $dom  = HtmlDomParser::str_get_html($response['content']);
        $json = $dom->findOne('.media script[type="text/x-magento-init"]')->innerText();
        $json = json_decode($json, true);
        if (!isset($json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'])) {
            return ['GalleryImage' => ''];
        }
        $json = $json['[data-gallery-role=gallery-placeholder]']['mage/gallery/gallery']['data'];
        $json = array_filter(array_column($json, 'img'));
        return ['GalleryImage' => implode(';;', $json)];
    }

    private function parseGalleryVideo($response)
    {
        // maybe not exist video in hk eshop.
        return ['GalleryVideo' => ''];
    }
}