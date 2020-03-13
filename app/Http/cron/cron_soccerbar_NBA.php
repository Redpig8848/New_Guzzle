<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('http://www.soccerbar.cc/forum-9-1.html','http://www.soccerbar.cc/forum-8-1.html','http://www.soccerbar.cc/forum-10-1.html',
    'http://www.soccerbar.cc/forum-15-1.html','http://www.soccerbar.cc/forum-17-1.html','http://www.soccerbar.cc/forum-63-1.html',
    'http://www.soccerbar.cc/forum-21-1.html'
);

foreach ($haomen as $key => $item) {

    //获取列表页
    try {
        $html = file_get_contents($item);
    } catch (Exception $exception) {
        continue;
    }
    $html = mb_convert_encoding($html, 'utf-8', 'gbk');

//拆分为数组
    $html = explode('</tbody>', $html);
//print_r($html);

    foreach ($html as $value) {
        //获取发布时间
        preg_match("/<span>(.*?)<\/span>/", $value, $contents);
        if (count($contents) == 0)
            preg_match("/<span class=\"xi1\">(.*?)<\/span>/", $value, $contents);
        if (preg_match("/<span>(.*?)<\/span>/", $value) || preg_match("/<span class=\"xi1\">(.*?)<\/span>/", $value)) {
            if (preg_match('/\d+-\d+-\d+ \d+:\d+/', $contents[1])) {
                //判断发布的时间是否为新
                $time = strtotime($contents[1]);
                $time_now = strtotime(date('Y-m-d H:i:s'));
                if ($time_now - $time < 3600) {
                       $time = $time - 28800;
//                print $value;
                    //进入内容页
                    preg_match('/<\/em> <a href="(.*?)" onclick/', $value, $href);
//                    print $href[1];
                    $body = file_get_contents($href[1]);
                    $body = mb_convert_encoding($body, 'utf-8', 'gbk');

                    // 获取页面id
                    $sub = substr($body, strpos($body, 'postmessage_') + 12);
                    $id = substr($sub, 0, strpos($sub, '"'));

                    // 匹配需要的内容
                    preg_match("/<td class=\"t_f\" id=\"postmessage_{$id}\">.*?<\/td>/s", $body, $txt);

                    $preg = array(
                        '/\w+.jpg/', '/\d+.\d+ KB,/', '/\( 下载次数: \d+\)/', '/下载附件/', '/保存到相册/', '/上传/',
                        '/\d+-\d+-\d+ \d+:\d+/', '/\( 下载次数: \d+, 售价: \d+ 点财富\)/', '/点击文件名/', '/售价: \d+ 点财富/',
                        '/\w+.png/', '/\w+.gif/', '/\d+.\d+ MB,/', '/直播吧/', '/ET足球网/', '/<img.*?>/', '/灵犀足球网官方微信群：/',
                        '/&nbsp;/'
                    );
                    $str = preg_replace($preg, '', strip_tags($txt[0]));
                    $str = str_replace('( 下载次数: 0)', '', $str);
//                    print($str);

                    // $content  正文内容
                    $content = trim($str);
                    //匹配标题
                    preg_match('/<title>(.*?)- ＝/', $body, $titles);
                    // $title 标题
                    $title = $titles[1];
                    // 以标题判断是否含有重复
                    $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
                    if ($bool > 0) {
                        continue;
                    }
                    // 在ID表中插入ID并获取用于本次新增
//                    $pid = 0;
                    $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
                    // 匹配类别 在$value中进行匹配
                    preg_match('/<em>\[<a(.*?)<\/a>/', $value, $types);
                    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $types[1], $cn_name);
                    // $type 类别
                    $type = implode('', $cn_name[0]);
                    // 以类别获取类别ID
                    $type_id = Check($key,$type);

                    // 获取Fid fid为栏目类型
                    $fid = FId($key);

                    //   开始赋值表内容   thread表   post表    post_tableid表（id表在进入开始就已）
                    //thread表内容开始
                    $data['fid'] = $fid;
                    $data['posttableid'] = 0;
                    $data['typeid'] = $type_id;
                    $data['sortid'] = 0;
                    $data['readperm'] = 0;
                    $data['price'] = 0;
                    $data['author'] = '天下足球版主';
                    $data['authorid'] = 4;
                    $data['subject'] = $title;
                    $data['dateline'] = $time;
                    $data['lastpost'] = $time;
                    $data['lastposter'] = '天下足球版主';
                    $data['views'] = rand(10, 100);

                    //post表
                    $article['fid'] = $fid;
                    $article['first'] = 1;
                    $article['author'] = $data['author'];
                    $article['authorid'] = $data['authorid'];
                    $article['subject'] = $data['subject'];
                    $article['dateline'] = $data['dateline'];
                    $article['message'] = $content;
                    $article['useip'] = '127.0.0.1';
                    $article['port'] = 51608;
//                    $article['htmlon'] = 1;
//                    $article['bbcodeoff'] = -1;
//                    $article['smileyoff'] = -1;
                    // 加入表关系ID
                    $data['tid'] = $pid;
                    $article['tid'] = $data['tid'];
                    $article['pid'] = $data['tid'];
//                    print_r($article);
//                    exit();
//                C::t('forum_post')->insert($article);
//                C::t('forum_thread')->insert($data);
                    DB::insert('forum_post', $article);
                    DB::insert('forum_thread', $data);
                    $todayposts =  DB::result_first("select todayposts from " . DB::table('forum_forum') . " where fid={$fid}");
                    $todayposts += 1;
                    DB::update('forum_forum', array('todayposts'=>$todayposts),array('fid'=> $fid));

                }


            }
        }
    }
}

function FId($key)
{
    switch ($key) {
        case '0':
            return 46;
            break;
        case '1':
            return 47;
            break;
        case '2':
            return 48;
            break;
        case '3':
            return 49;
            break;
        case '4':
            return 50;
            break;
        case '5':
            return 51;
            break;
        case '6':
            return 52;
            break;
        default:
            return 50;
            break;
    }
}

function Check($key, $type)
{
    if ($key == 0)
        return YingChao($type);
    elseif ($key == 1)
        return YiJia($type);
    elseif ($key == 2)
        return XiJia($type);
    elseif ($key == 3)
        return DeJia($type);
    elseif ($key == 4)
        return TianXiaZuQiu($type);
    elseif ($key == 5)
        return LanQiu($type);
    elseif ($key == 6)
        return China($type);
}


//英超足球论坛
function YingChao($name){
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
            return 2;
    }
}

//意甲足球论坛
function YiJia($name){
    switch ($name){
        case '意甲':
            return 17;
            break;
        case '国米':
            return 18;
            break;
        case '米兰':
            return 19;
            break;
        case '尤文':
            return 20;
            break;
        case '留言板':
            return 21;
            break;
        case '战况':
            return 22;
            break;
        case '原创':
            return 23;
            break;
        case '转帖':
            return 24;
            break;
        case '新闻':
            return 25;
            break;
        case '讨论':
            return 26;
            break;
        case '球员':
            return 27;
            break;
        case '图片':
            return 28;
            break;
        case '其他':
            return 29;
            break;
        case '国家队':
            return 30;
            break;
        default:
            return 17;
    }
}

//西甲足球论坛
function XiJia($name){
    switch ($name){
        case '西甲':
            return 31;
            break;
        case '皇马':
            return 32;
            break;
        case '巴萨':
            return 33;
            break;
        case '战况':
            return 34;
            break;
        case '原创':
            return 35;
            break;
        case '转帖':
            return 36;
            break;
        case '新闻':
            return 37;
            break;
        case '讨论':
            return 38;
            break;
        case '球员':
            return 39;
            break;
        case '图片':
            return 40;
            break;
        case '其他':
            return 41;
            break;
        case '国家队':
            return 42;
            break;
        default:
            return 31;
    }
}

//德甲足球论坛
function DeJia($name){
    switch ($name){
        case '德甲':
            return 43;
            break;
        case '拜仁':
            return 44;
            break;
        case '流言板':
            return 45;
            break;
        case '周边新闻':
            return 46;
            break;
        case '采访':
            return 47;
            break;
        case '战况':
            return 48;
            break;
        case '原创':
            return 49;
            break;
        case '转帖':
            return 50;
            break;
        case '新闻':
            return 51;
            break;
        case '讨论':
            return 52;
            break;
        case '球员':
            return 53;
            break;
        case '图片':
            return 54;
            break;
        case '其他':
            return 55;
            break;
        case '国家队':
            return 56;
            break;
        default:
            return 43;
    }
}

//天下足球_欧洲杯_欧冠
function TianXiaZuQiu($name){
    switch ($name){
        case '欧冠':
            return 57;
            break;
        case '战况':
            return 58;
            break;
        case '欧洲杯':
            return 59;
            break;
        case '原创':
            return 60;
            break;
        case '转帖':
            return 61;
            break;
        case '新闻':
            return 62;
            break;
        case '讨论':
            return 63;
            break;
        case '球员':
            return 64;
            break;
        case '图片':
            return 65;
            break;
        case '流言板':
            return 66;
            break;
        case '欧洲':
            return 67;
            break;
        case '采访':
            return 68;
            break;
        case '主帅':
            return 69;
            break;
        case '其他':
            return 70;
            break;
        case '女足':
            return 71;
            break;
        case '世界杯':
            return 72;
            break;
        default:
            return 57;
    }
}

//篮球论坛_nba_cba
function LanQiu($name){
    switch ($name){
        case '国内篮球':
            return 73;
            break;
        case 'NBA':
            return 74;
            break;
        case '原创':
            return 75;
            break;
        case '转帖':
            return 76;
            break;
        case '新闻':
            return 77;
            break;
        case '讨论':
            return 78;
            break;
        case '球员':
            return 79;
            break;
        case '流言板':
            return 80;
            break;
        case 'CBA':
            return 81;
            break;
        default:
            return 77;
    }
}

//中国足球_中超_中甲
function China($name){
    switch ($name){
        case '中超':
            return 82;
            break;
        case '恒大':
            return 83;
            break;
        case '中甲':
            return 84;
            break;
        case '流言板':
            return 85;
            break;
        case '亚冠':
            return 86;
            break;
        case '战况':
            return 87;
            break;
        case '原创':
            return 88;
            break;
        case '转帖':
            return 89;
            break;
        case '新闻':
            return 90;
            break;
        case '讨论':
            return 91;
            break;
        case '球员':
            return 92;
            break;
        case '图片':
            return 93;
            break;
        case '女足':
            return 94;
            break;
        case '其他':
            return 95;
            break;
        case '海外球员':
            return 96;
            break;
        case '国家队':
            return 97;
            break;
        default:
            return 90;
    }
}
























