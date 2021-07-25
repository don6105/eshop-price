<?php

namespace App\Services\GameCrawler\Parsers;

use voku\helper\HtmlDomParser;

class BaseParser {
    public function initDom(String $content):Void
    {
        $this->dom = HtmlDomParser::str_get_html($content);
    }

    public function getData():Array
    {
        $class   = new \ReflectionClass(get_class($this));
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $parser_methods = [];
        foreach ($methods as $method) {
            $method = $method->getName();
            if (stripos($method, 'parse') === false) { continue; }
            $table_colum = str_ireplace('parse', '', $method);
            $parser_methods[ $table_colum ] = call_user_func([$this, $method]);
        }
        return $parser_methods;
    }
}
