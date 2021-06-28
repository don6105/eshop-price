<?php
namespace App\Libraries; 

class Curl {
    private $header;

    public function run($url, $timeout = 30, $data = null, $maxRequestNum = 3)
    {
        $cookie_name = storage_path().'/cookie.cookie';
        $request_url = $url;
        $header      = isset($this->header)? $this->header : $this->getHeader();
        if (empty($data)) {
            $post_fields = '';
        } elseif (is_string($data)) {
            $post_fields = $data;
        } else {
            $post_fields = http_build_query($data);
        }
        
        $option = [
            CURLOPT_VERBOSE          => 0,
            CURLOPT_RETURNTRANSFER   => 1,
            CURLOPT_FOLLOWLOCATION   => true,
            CURLOPT_HEADER           => false,
            CURLOPT_HTTPHEADER       => $header,
            CURLOPT_NOBODY           => false,
            CURLOPT_CUSTOMREQUEST    => empty($data)? 'GET' : 'POST',
            CURLOPT_POSTFIELDS       => $post_fields,
            CURLOPT_SSL_VERIFYPEER   => false,
            CURLOPT_SSL_VERIFYHOST   => 2,
            CURLOPT_COOKIEFILE       => $cookie_name,
            CURLOPT_COOKIEJAR        => $cookie_name,
            CURLOPT_NOPROGRESS       => true,
            CURLOPT_IPRESOLVE        => CURL_IPRESOLVE_V4
        ];

        $result = [];
        for($request_index = 1; $request_index <= $maxRequestNum; ++$request_index) {
            // init curl
            $ch = curl_init();
            curl_setopt_array($ch, $option);
            curl_setopt($ch, CURLOPT_URL, $request_url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->getRandomUserAgent($request_index == 1));
            $r = curl_exec($ch);
            // get result from curl
            $result = [];
            $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result['final_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $result['fail']      = curl_error($ch);
            $result['content']   = $r; // put response at end of result.
            curl_close($ch);
            // retry
            if($result['http_code'] == 200) {
                $request_index = $maxRequestNum + 1;
            } elseif( $this->isTimeOut($result) || in_array($result['http_code'], [301, 302])) {
                $timeout += 2;
            }
        }
        is_file($cookie_name) && @unlink($cookie_name);
        return $result;
    }

    public function setHeader(array $header = null)
    {
        $this->header = $header;
    }

    public function isSuccess($response)
    {
        if (!empty($response['fail'])) {
            return false;
        }
        if (!isset($response['http_code']) || !isset($response['content'])) {
            return false;
        }
        if ($response['http_code'] >= 400) {
            return false;
        }
        if (empty($response['content'])) {
            return false;
        }
        return true;
    }


    private function getHeader()
    {
        $header = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US;q=0.8,en;q=0.7',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests:1'
        ];
        return $header;
    }

    private function getRandomUserAgent($reset = true)
    {
        static $user_agents;
        if($reset || empty($user_agents)) {
            $user_agents = [
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063',
                'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
                'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; InfoPath.3; rv:11.0) like Gecko',
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
                'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)'
            ];
        }
        shuffle($user_agents);
        return array_pop($user_agents);
    }

    private function isTimeOut(array $result)
    {
        if(isset($result['http_code']) && $result['http_code'] == 0) {
            return true;
        }
        if(!empty($result['fail']) && preg_match('/time(d)?[ _]?out/i', $result['fail']) > 0) {
            return true;
        }
        return false;
    }
}