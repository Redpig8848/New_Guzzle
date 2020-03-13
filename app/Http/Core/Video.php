<?php

$options = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
);


$html  = file_get_contents("https://www.huanhuba.com/info/140809.html",false,
    stream_context_create($options));

$txt = substr($html,strpos($html,'<div class="inteldesc">')+23);
$txt = substr($txt,0,strpos($txt,'本场比赛前瞻由欢呼吧足球AI智能编辑系统发布。'));
$txt = preg_replace('/<a .*?>/','',$txt);
$txt = str_replace('</a>','',$txt);
echo trim($txt);
preg_match('/\d+-\d+-\d+ \d+:\d+:\d+/',$html,$times);

print_r($times);















































