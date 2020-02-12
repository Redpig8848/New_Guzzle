<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class GuzzleController extends Controller
{
    //

    private $totalPageCount;
    private $counter = 1;
    private $concurrency = 300;
    private $num = 85607;

    function index()
    {

        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $url = array();
        $file = fopen(public_path('url.txt'),"w");
        for ($i = 1; $i <= 227; $i++) {
            $url[$i - 1] = "http://www.hangxun100.com/forum-50-{$i}.html";
        }
//        dd($url);
        $this->totalPageCount = 1000;
        $client = new Client();
        $requests = function ($total) use ($client, $url) {
            foreach ($url as $item) {
                yield function () use ($item, $client) {
                    return $client->getAsync($item);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use ($file) {
                ob_flush();
                flush();
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                try {
                    $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gb2312'));
                } catch (\Exception $exception) {
                    $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gbk'));
                }
                $arr = $crawler->filter('#threadlisttableid > tbody')->each(function ($node, $i) use ($http,$file) {
                    if ($node->text() !== ""){
                        try {
                            $href = $node->filter('tr > th > div:nth-child(1) > a.s.xst')->attr('href');
                            $href = "http://www.hangxun100.com/".$href;
                            echo $href."<br>";
                            fwrite($file,$href.chr(10));
                        }catch (\Exception $exception){
                            echo "空，跳过"."<br>";
                        }

                    }
                });

                echo '<br>';
                $this->countedAndCheckEnded();
            },
            'rejected' => function ($reason, $index) {
                $this->error("Error is " . $reason);
                $this->countedAndCheckEnded();
            }
        ]);
        $promise = $pool->promise();
        $promise->wait();

        fclose($file);
    }


    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount) {
            return;
        }
    }
}
