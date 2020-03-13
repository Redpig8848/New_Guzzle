<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('https://www.huanhuba.com/info/live/9.html','https://www.huanhuba.com/info/live/8.html',
    'https://www.huanhuba.com/info/live/13.html','https://www.huanhuba.com/info/live/7.html');
$options = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
);

foreach ($haomen as $key => $item) {
    //获取列表页
    try {
        $html = file_get_contents($item,false,
            stream_context_create($options));
    } catch (Exception $exception) {
        continue;
    }

//拆分为数组
    $html  = explode('<div class="intelcell"',$html);
//print_r($html);

    foreach ($html as $value) {
        if (strpos($value,'intel-link') !== false){
            // $title 标题
            $title = substr($value,strpos($value,'<div class="desc "'));
            $title = substr($title,0,strpos($title,'</div>')+6);
            $title = trim(strip_tags($title));
            // 以标题判断是否含有重复
            $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
            if ($bool > 0) {
                continue;
            }
            preg_match('/href="(.*?)"/',$value,$hrefs);
            $href = "https://www.huanhuba.com".$hrefs[1];

            $body_html =file_get_contents($href,false,stream_context_create($options));
            $txt = substr($body_html,strpos($body_html,'<div class="inteldesc">')+23);
            $txt = substr($txt,0,strpos($txt,'本场比赛前瞻由欢呼吧足球AI智能编辑系统发布。'));
            $txt = preg_replace('/<a .*?>/','',$txt);
            $txt = str_replace('</a>','',$txt);
            $content = trim($txt);

            preg_match('/\d+-\d+-\d+ \d+:\d+:\d+/',$body_html,$times);
            $time = strtotime($times[0]);
//            $time = $time- 28800;


            // 在ID表中插入ID并获取用于本次新增
            $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
//            $pid = 0;
            // 以类别获取类别ID
            $type_id = Check($key);

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
            $article['htmlon'] = 1;
            $article['bbcodeoff'] = -1;
            $article['smileyoff'] = -1;

            // 加入表关系ID
            $data['tid'] = $pid;
            $article['tid'] = $data['tid'];
            $article['pid'] = $data['tid'];

//            print_r($article);

            DB::insert('forum_post', $article);
            DB::insert('forum_thread', $data);
            $todayposts =  DB::result_first("select todayposts from " . DB::table('forum_forum') . " where fid={$fid}");
            $todayposts += 1;
            DB::update('forum_forum', array('todayposts'=>$todayposts),array('fid'=> $fid));
        }



    }




}

function FId($key)
{
    switch ($key) {
        case '0':
            return 49;
            break;
        case '1':
            return 46;
            break;
        case '2':
            return 47;
            break;
        case '3':
            return 48;
            break;
        default:
            return 50;
            break;
    }
}

function Check($key, $type = 'default')
{
    if ($key == 0)
        return DeJia($type);
    elseif ($key == 1)
        return YinGCHao($type);
    elseif ($key == 2)
        return YiJia($type);
    elseif ($key == 3)
        return XiJia($type);
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


//英超足球论坛
function YinGCHao($name){
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

