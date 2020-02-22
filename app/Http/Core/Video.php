<?php


use Illuminate\Support\Facades\DB;
echo "1".chr(10);
$file = file("./zuqiu.txt");
//print_r($file);
echo "2".chr(10);
$data['fid'] = 2;
$data['posttableid'] = 0;
$data['typeid'] = 0;
$data['sortid'] = 0;
$data['readperm'] = 0;
$data['price'] = 0;
$data['author'] = '视频版主';
$data['authorid'] = 2;
$data['tid'] = 1;
$data['subject'] = "demo";
//$html  = file_get_contents(trim($file[0]));
$html  = file_get_contents("http://www.hangxun100.com/thread-1711-1-164.html");
preg_match('', $html);
print $html;
