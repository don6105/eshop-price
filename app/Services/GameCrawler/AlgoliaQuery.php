<?php

namespace App\Services\GameCrawler;

class AlgoliaQuery {
    public function __construct(Object $config)
    {
        $this->Config = $config;
    }

    public function getQueryUrl()
    {
        $url    = $this->Config->getConfig('base.url', '');
        $api_id = $this->Config->getConfig('base.algolia_id', '');
        return str_replace('[[ALGOLIA_ID]]', $api_id, $url);
    }

    public function getQueryHeaders()
    {
        $api_id  = $this->Config->getConfig('base.algolia_id', '');
        $api_key = $this->Config->getConfig('base.algolia_key', '');
        return [
            "cache-control"            => "no-cache",
            "content-type"             => "application/json",
            "x-algolia-api-key"        => $api_key,
            "x-algolia-application-id" => $api_id
        ];
    }

    public function getQueryParams(String $range, String $order, Int $page, Int $num):String
    {
        $facets  = [
            "generalFilters",
            "platform",
            "availability",
            "genres",
            "howToShop",
            "virtualConsole",
            "franchises",
            "priceRange",
            "esrbRating",
            "playerFilters",
        ];
        $filters = [
            '["'.$range.'"]',
            '["availability:Available now"]',
            '["platform:Nintendo Switch"]',
        ];
        $filters = '['.implode(',', $filters).']';

        $param = new \stdClass();
        $param->indexName = $order;
        $param->params    = http_build_query([
            'query'             => '',
            'hitsPerPage'       => $num,
            'maxValuesPerFacet' => 30,
            'page'              => $page,
            'analytics'         => 'false',
            'facets'            => json_encode($facets),
            'tagFilters'        => '',
            'facetFilters'      => $filters
        ]);
        $request = new \stdClass();
        $request->requests = [$param];
        return json_encode($request);
    }
}
