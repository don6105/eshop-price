<?php

return [
    'base' => [
        'url'         => 'https://[[ALGOLIA_ID]]-dsn.algolia.net/1/indexes/*/queries',
        'algolia_id'  => 'U3B6GR4UA3',
        'algolia_key' => 'c4da8be7fd29f0f5bfa42920b0a99dc7',
        'order'       => [
            'ncom_game_en_us_title_asc',
            'ncom_game_en_us_title_des'
        ],
        'range'       => [
            'priceRange:Free to start',
            'priceRange:$0 - $4.99',
            'priceRange:$5 - $9.99',
            'priceRange:$10 - $19.99',
            'priceRange:$20 - $39.99',
            'priceRange:$40+'
        ],
    ],
    'ext' => [
        'GameSize'     => ['.file-size dd', 'innerText()'],
        'Language'     => ['.supported-languages dd', 'innerText()'],
        'Description'  => ['[itemprop="description"]', 'innerHtml()'],
        'TVMode'       => ['.playmode-tv img', 'alt'],
        'TabletopMode' => ['.playmode-tabletop img'],
        'HandheldMode' => ['.playmode-handheld img'],
    ],
    'gallery' => [
        'url'   => 'https://assets.nintendo.com/video/upload/sp_vp9_full_hd/v1/',
        'video' => ['product-gallery [type="video"]', 'video-id'],
        'image' => ['product-gallery [type="image"]', 'src'],
    ]
];