<?php

class GetParams {


    // Get Url Params
    public function GetUrlParams($get_position = 1)
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('/', $url);
        $url = array_filter($url);
        $url = array_slice($url, $get_position);
        return $url;
    }

    // Get Url Query Params
    public function GetUrlQueryParams()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = array_filter($url);
        // $url = array_slice($url, 1);
        return $url;
    }

    public function GetUrlQueryParamsArray()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = array_filter($url);
        $url = array_slice($url, 1);
        $url = explode('&', $url[0]);
        $url = array_filter($url);
        return $url;
    }

    public function GetUrlQueryParamsArrayAsArray()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = array_filter($url);
        $url = array_slice($url, 1);
        $url = explode('&', $url[0]);
        $url = array_filter($url);
        $url = array_map(function($item) {
            return explode('=', $item);
        }, $url);
        return $url;
    }

    public function GetUrlQueryParamsArrayAsArrayAsArray()
    {
        $result = [];
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = array_filter($url);
        $url = array_slice($url, 1);
        $url = explode('&', $url[0]);
        $url = array_filter($url);
        $url = array_map(function($item) {
            return explode('=', $item);
        }, $url);
        $url = array_filter($url);

        $result = array_map(function($item) {
            return [
                $item[0] => $item[1]
            ];
        }, $url);
        return $result;
    }

    public function GetQueryParams($Param = null)
    {
        $result = [];
        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = array_filter($url);
        $url = array_slice($url, 1);

        if(count($url) > 0) {
            $url = explode('&', $url[0]);
            $url = array_filter($url);
            $url = array_map(function($item) {
                return explode('=', $item);
            }, $url);
            $url = array_filter($url);
            for($i = 0; $i < count($url); $i++) {
                $result[$url[$i][0]] = $url[$i][1];
            }
        } else {
            $result = [];
        }

        if($Param && isset($result[$Param])) {
            return $result[$Param];
        }
        return $result;
    }

}