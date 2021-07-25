<?php

namespace App\Services\GameCrawler\Parsers;

use App\Services\GameCrawler\Interfaces\Parser;
use GuzzleHttp\Client;

Class ParserUs extends BaseParser implements Parser {
    public function __get($name)
    {
        if (strcasecmp($name, 'languages') === 0) {
            $selector = '.supported-languages dd';
            $this->languages = $this->dom->findOne($selector)->innerText();
            return $this->languages;
        }
    }

    public function parseTitle()
    {
        $selector = '.hero-data .game-title';
        return $this->dom->findOne($selector)->innerText();
    }

    public function parsePrice()
    {
        $selector = '.price .msrp';
        $price    = $this->dom->findOne($selector)->innerText();
        $price    = preg_match('/[\d\.]+/', $price, $m) > 0? $m[0] : $price;
        return floatval($price);
    }

    public function parseGameSize()
    {
        $selector = '.file-size dd';
        return $this->dom->findOne($selector)->innerText();
    }

    public function parseDescription()
    {
        $selector = '[itemprop="description"]';
        return $this->dom->findOne($selector)->innerHtml();
    }

    public function parseTVMode()
    {
        $selector = '.playmode-tv img';
        $alt      = $this->dom->findOne($selector)->getAttribute('alt');
        return stripos($alt, 'not supported') === false ? 1 : 0;
    }

    public function parseTabletopMode()
    {
        $selector = '.playmode-tabletop img';
        $alt      = $this->dom->findOne($selector)->getAttribute('alt');
        return stripos($alt, 'not supported') === false ? 1 : 0;
    }

    public function parseHandheldMode()
    {
        $selector = '.playmode-handheld img';
        $alt      = $this->dom->findOne($selector)->getAttribute('alt');
        return stripos($alt, 'not supported') === false ? 1 : 0;
    }

    public function parseSupportEnglish()
    {
        return stripos($this->languages, 'english') !== false? 1 : 0;
    }

    public function parseSupportChinese()
    {
        return stripos($this->languages, 'chinese') !== false? 1 : 0;
    }

    public function parseSupportJapanese()
    {
        return stripos($this->languages, 'japanese') !== false? 1 : 0;
    }

    public function parseGalleryVideo()
    {
        $selector = 'product-gallery [type="video"]';
        $video_id = $this->dom->findOne($selector)->getAttribute('video-id');
        $url  = 'https://assets.nintendo.com/video/upload/sp_vp9_full_hd/v1/';
        $url .= $video_id.'.mpd';

        $client      = new Client();
        $status_code = null;
        try {
            $response    = $client->request('GET', $url);
            $status_code = $response->getStatusCode();
        } catch(\Throwable $t) {}

        $videos = [];
        if ($status_code === 200) {
            $content  = $response->getBody()->getContents();
            $pattern  = '/<baseurl>([^\n]+)<\/baseurl>/i';
            $base_url = 'https://assets.nintendo.com';
            if (preg_match_all($pattern, $content, $m) > 0) {
                $videos = array_filter($m[1], function($v) {
                    return stripos($v, 'h_720');
                });
                $videos = array_map(function($u) use ($base_url) {
                    return $base_url.$u;
                }, $videos);
            }
        }
        return empty($videos) ? '' : implode(';;', $videos);
    }

    public function parseGalleryImage()
    {
        $selector = 'product-gallery [type="image"]';
        $images   = $this->dom->find($selector)->src;
        return empty($images)? '' : implode(';;', $images);
    }
}