<?php

namespace App\Services\GameCrawler;

class AlgoliaResponse {
    public $total_num = 0;
    public $game_data = [];

    private $data = [];
    
    public function __construct($response)
    {
        $this->parseResponse($response);
        $this->setTotalNum();
        $this->setGameData();
    }



    private function parseResponse($response)
    {
        try {
            $response   = $response['value']->getBody()->getContents();
            $json_array = json_decode($response, true);
            $this->data = $json_array['results'][0]?? [];
        } catch(\Throwable $t) {}
    }

    private function setTotalNum()
    {
        $this->total_num = intval($this->data['nbHits']?? 0);
    }

    private function setGameData()
    {
        $data = $this->data['hits']?? [];
        $this->game_data = $this->parseGameData($data);
    }

    private function parseGameData(Array $data):array
    {
        $game_data = [];
        foreach ($data as $row) {
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
                'Price'        => $row['salePrice'] ?? $row['msrp']?? 0.0,
            ];
        }
        return $game_data;
    }
}