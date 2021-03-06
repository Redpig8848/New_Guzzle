<?php

namespace App\Http\Controllers\ZuQiuBa;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class ArticleController extends Controller
{
    //

    private $totalPageCount;
    private $counter = 1;
    private $concurrency = 300;
    private $num = 104851;

    function index()
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $url = array();
//        $file = fopen(public_path('url.txt'),"r");
        $urls = file(public_path('url3.txt'));
//        $urls = '111';
//        dd($urls[0]);
//        dd($url);
        $this->totalPageCount = count($urls);
//        dd($this->totalPageCount);
        $client = new Client();
        $requests = function ($total) use ($client, $urls) {
            foreach ($urls as $item) {
                yield function () use ($item, $client) {
                        return $client->get(trim($item));
                };
            }
        };
        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => 20,
            'fulfilled' => function ($response, $index)  {
                try {
                    $http = $response->getBody()->getContents();
                    $crawler = new Crawler();
                    try {
                        $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gb2312'));
                    }catch (\Exception $exception){
                        $crawler->addHtmlContent(mb_convert_encoding($http, 'utf-8', 'gbk'));
                    }
                    $sub  = substr($http, strpos($http, 'postmessage_')+12);
                    $id = substr($sub,0,strpos($sub,'"'));

                    //thread表内容开始
                    $data['fid'] = 46;
                    $data['posttableid'] = 0;
                    $data['typeid'] = 0;
                    $data['sortid'] = 0;
                    $data['readperm'] = 0;
                    $data['price'] = 0;
                    $data['author'] = '天下足球版主';
                    $data['authorid'] = 4;
                    $data['subject'] = $crawler->filter('#postlist > table:nth-child(1) > tbody > tr > td.plc.ptm.pbn.vwthd > h1')->text();

    //                try {
    //                    $type = $crawler->filter('#postlist > div.archy_bmtt.mbm > div > table > tbody > tr > td.plc.ptm.pbn > h1 > a')->text();
    //                    $type = str_replace(array('[',']'),'',$type);
    //                    $data['typeid'] = $this->Check($type);
    //                }catch (\Exception $exception){
                    $data['typeid'] = rand(2,16);
    //                }
                    try {
                        $time_str = $crawler->filter('#authorposton'.$id)->text();
                        $time = str_replace('发表于 ','',$time_str);
                        $data['dateline'] = strtotime($time);
                    }catch (\Exception $exception){
                        $data['dateline'] = strtotime(date('Y-m-d H:i:s'));
                    }
                    $data['lastpost'] = $data['dateline'];
                    $data['lastposter'] = '天下足球版主';
                    $data['views'] = rand(1,100);
                    echo 1;
                    //end


                    //post表
                    $article['fid'] = 46;
                    $article['first'] = 1;
                    $article['author'] = $data['author'];
                    $article['authorid'] = $data['authorid'];
                    $article['subject'] = $data['subject'];
                    $article['dateline'] = $data['dateline'];
                    //内容
                    $data_a = $crawler->filterXPath('//*[@id="postmessage_'.$id.'"]')->text();
                    $preg = array(
                        '/\w+.jpg/', '/\d+.\d+ KB,/', '/\( 下载次数: \d+\)/', '/下载附件/', '/保存到相册/','/上传/',
                        '/\d+-\d+-\d+ \d+:\d+/','/\( 下载次数: \d+, 售价: \d+ 点财富\)/','/点击文件名/','/售价: \d+ 点财富/',
                        '/\w+.png/','/\w+.gif/','/\d+.\d+ MB,/','/直播吧/','/ET足球网/'
                    );
                    $spt = array('1 天前','3 天前','2 天前','4 天前','5 天前','6 天前','7 天前','[记录]','[购买]');
                    $str = trim(preg_replace($preg, '', $data_a));
                    $article['message'] =str_replace($spt, '', $str);
                    $article['useip'] = '127.0.0.1';
                    $article['port'] = 51608;
                    //end
                    // 加入表关系ID
                    $data['tid'] = $this->num++;
                    $article['tid'] = $data['tid'];
                    $article['pid'] = $data['tid'];
                    $p['pid'] = $article['pid'];

    //                dd($article);
                    try{
                        echo 6;
                        $bool_a = DB::table('pre_forum_post')->insert($article);
                    }catch (\Exception $exception){
                        exit($exception);
                    }
                    echo 7;

                    $p_bool = DB::table('pre_forum_post_tableid')->insert($p);
                    try {
                        $bool = DB::table('pre_forum_thread')->insert($data);
                    }catch (\Exception $exception){
                        dd($exception);
                    }
                    echo $bool == 1 && $bool_a == 1 ? '成功插入'.$data['subject'].'到数据库' : '插入失败';
                    echo '<br>';
                }catch (\Exception $exception){
                    echo "tiaoguo"."<br>";
                }

            },
            'rejected' => function ($reason, $index) {
                $this->error("Error is " . $reason);
                $this->countedAndCheckEnded();
            }
        ]);
        $promise = $pool->promise();
        $promise->wait();

    }


    public function Check($name){
        switch ($name){
            case '英超':
                return 2;
                break;
            case '曼联':
                return 3;
                break;
            case '切尔西':
                return 4;
                break;
            case '利物浦':
                return 5;
                break;
            case '阿森纳':
                return 6;
                break;
            case '曼城':
                return 7;
                break;
            case '战况':
                return 8;
                break;
            case '原创':
                return 9;
                break;
            case '转帖':
                return 10;
                break;
            case '新闻':
                return 11;
                break;
            case '讨论':
                return 12;
                break;
            case '球员':
                return 13;
                break;
            case '图片':
                return 14;
                break;
            case '其他':
                return 15;
                break;
            case '国家队':
                return 16;
                break;
            default:
                return 0;
        }
    }

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount) {
            return;
        }
    }
}
