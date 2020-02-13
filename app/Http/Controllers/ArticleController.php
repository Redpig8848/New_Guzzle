<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class ArticleController extends Controller
{
    //
    private $totalPageCount;
    private $counter = 1;
    private $concurrency = 300;
    private $num = 86636;

    function index()
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $url = array();
//        $file = fopen(public_path('url.txt'),"r");
        $urls = file(public_path('et3.txt'));
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
            'concurrency' => 5,
            'fulfilled' => function ($response, $index)  {
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
                $data['fid'] = 65;
                $data['posttableid'] = 0;
                $data['typeid'] = 0;
                $data['sortid'] = 0;
                $data['readperm'] = 0;
                $data['price'] = 0;
                $data['author'] = '休闲专区版主';
                $data['authorid'] = 6;
                $data['subject'] = $crawler->filter('#thread_subject > a')->text();

                try {
                    $type = $crawler->filter('#postlist > div.archy_bmtt.mbm > div > table > tbody > tr > td.plc.ptm.pbn > h1 > a')->text();
                    $type = str_replace(array('[',']'),'',$type);
                    $data['typeid'] = $this->Check($type);
                }catch (\Exception $exception){
                    $data['typeid'] = rand(186,197);
                }
                try {
                    $time_str = $crawler->filter('#authorposton'.$id)->text();
                    $time = str_replace('发表于 ','',$time_str);
                    $data['dateline'] = strtotime($time);
                }catch (\Exception $exception){
                    $data['dateline'] = strtotime(date('Y-m-d H:i:s'));
                }
                $data['lastpost'] = $data['dateline'];
                $data['lastposter'] = '休闲专区版主';
                $data['views'] = rand(1,100);
                echo 1;
                //end


                //post表
                $article['fid'] = 65;
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
            case '原创':
            case '游戏':
                return 186;
                break;
            case '精彩推荐':
                return 187;
                break;
            case '转帖':
            case '转载':
                return 188;
                break;
            case '新闻':
                return 189;
                break;
            case '讨论':
            case '话题':
                return 190;
                break;
            case '祝福':
                return 191;
                break;
            case '闲聊':
            case '教程':
                return 192;
                break;
            case '召集':
                return 193;
                break;
            case '恶搞':
                return 194;
                break;
            case '视频':
            case '数码':
                return 195;
                break;
            case '图片':
                return 196;
                break;
            case '音乐':
                return 197;
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
