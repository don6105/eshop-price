<?php

namespace App\Services;

use App\Contracts\Exchange as ExchangeContract;
use App\Services\Base as BaseService;
use App\Libraries\Curl as CurlLib;
use App\Models\Exchange as ExchangeModel;
use voku\helper\HtmlDomParser;

class Exchange extends BaseService implements ExchangeContract
{
    private $exchange_url         = 'https://rate.bot.com.tw/xrt';
    private $country_currency_url = 'https://www.ups.com/worldshiphelp/WSA/ENG/AppHelp/mergedProjects/CORE/Codes/Country_Territory_and_Currency_Codes.htm';

    public function getExchangeRate()
    {
        $Curl             = new CurlLib();
        $response         = $Curl->run($this->exchange_url);
        $exchange_rate    = $this->parseExchangeTable($response);
        $response         = $Curl->run($this->country_currency_url);
        $country_currency = $this->parseCountryCurrencyTable($response);
        $exchange_data    = $this->mergeData($exchange_rate, $country_currency);
        $this->saveExchangeData($exchange_data);
    }

    private function parseExchangeTable($response)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom    = HtmlDomParser::str_get_html($response['content']);
        $target = $dom->find('table[title="牌告匯率"] tr');
        $result = [];
        foreach ($target as $row) {
            $currency = $row->findOne('[data-table="幣別"] .print_show')->innerText();
            $currency = trim(html_entity_decode($currency));
            $currency = preg_match('/\(([a-z]+)\)/i', $currency, $m) > 0? $m[1] : '';

            $rate = $row->findOne('[data-table="本行現金賣出"]')->innerText();
            $rate = trim(html_entity_decode($rate));

            if (!empty($currency) && is_numeric($rate)) {
                $result[$currency] = floatval($rate);
            }
        }
        $result['TWD'] = 1.0;   // 加入臺幣匯率
        return $result;
    }

    private function parseCountryCurrencyTable($response)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom    = HtmlDomParser::str_get_html($response['content']);
        $target = $dom->find('table tr');
        $result = [];
        foreach ($target as $row) {
            $country = $row->find('td', 2)->findOne('p')->innerText();
            $country = trim(html_entity_decode($country));

            $currency = $row->find('td', 3)->findOne('p')->innerText();
            $currency = trim(html_entity_decode($currency));
            if (strlen($country) == 2) {
                $result[$country] = $currency;
            }
        }
        return $result;
    }

    private function mergeData($currencyRate, $countryCurrency)
    {
        $result = [];
        foreach ($countryCurrency as $country => $currency) {
            if (isset($currencyRate[$currency])) {
                $result[] = [
                    'Country'  => strtoupper($country),
                    'Currency' => strtoupper($currency),
                    'Rate'     => $currencyRate[$currency]
                ];
            }
        }
        return $result;
    }

    private function saveExchangeData($exchange_data)
    {
        $Exchange = new ExchangeModel();
        foreach ($exchange_data as $row) {
            $row['UpdateTime'] = date('Y-m-d H:i:s');
            $Exchange->insertOrUpdate($row);
        }
    }
}