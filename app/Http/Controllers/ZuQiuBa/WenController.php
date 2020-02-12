<?php

namespace App\Http\Controllers\ZuQiuBa;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;

class WenController extends Controller
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
//        $file = fopen(public_path('url.txt'),"r");
        $urls = file(public_path('et.txt'));
        dd($urls);
//        dd($url);
        $this->totalPageCount = 1000;
        $client = new Client();
        $requests = function ($total) use ($client, $urls) {
            foreach ($urls as $item) {
                yield function () use ($item, $client) {
                    return $client->get($item);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index)  {
                ob_flush();
                flush();
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                try {
                    $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gb2312'));
                } catch (\Exception $exception) {
                    $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gbk'));
                }
                dd(1);
                $sub  = substr($http, strpos($http, 'postmessage_')+12);
                $id = substr($sub,0,strpos($sub,'"'));
                $data_a = $crawler->filterXPath('//*[@id="postmessage_'.$id.'"]')->text();
                dd($data_a);
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

    }


    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount) {
            return;
        }
    }
}
