<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ArticleController extends Controller
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
        $urls = file(public_path('url.txt'));
//        dd($urls);
//        dd($url);
        $this->totalPageCount = 1000;
        $client = new Client();
        $requests = function ($total) use ($client, $urls) {
            foreach ($urls as $item) {
                yield function () use ($item, $client) {
                    return $client->getAsync($item);
                };
            }
        };
        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index)  {
                dd(2);
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
