<?php
\think\Route::get('doc/assets', "\\Api\\Doc\\DocController@assets",['deny_ext'=>'php|.htacess']);
\think\Route::get('doc', "\\Api\\Doc\\DocController@index");
\think\Route::get('doc/list', "\\Api\\Doc\\DocController@getList");
\think\Route::get('doc/info', "\\Api\\Doc\\DocController@getInfo");
\think\Route::any('doc/debug', "\\Api\\Doc\\DocController@debug");

function http_request($url, $cookie, $data = null, $headers = array()){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (count($headers)){
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    if (!empty($cookie)){
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
