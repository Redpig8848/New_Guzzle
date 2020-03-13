<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('http://zuqiucctv.com/forum-yinchaoqiudui-1.html','http://zuqiucctv.com/forum-47-1.html',
    'http://zuqiucctv.com/forum-57-1.html','http://zuqiucctv.com/forum-51-1.html'
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
        if (preg_match('/<a href="http:\/\/zuqiucctv\.com\/(.*?)\.html" onclick/',$value,$hrefs)) {

//                print $value;
            //进入内容页
            $href =  "http://zuqiucctv.com/".$hrefs[1].".html";
            $pre = '/<a href="http:\/\/zuqiucctv\.com\/'.$hrefs[1].'\.html"(.*?)<\/a>/';
            preg_match($pre,$value,$titles);
            $title = substr($titles[1],strpos($titles[1],'>')+1);
            // 以标题判断是否含有重复
            $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
            if ($bool > 0) {
                break;
            }
            $body = file_get_contents($href);
            $body = mb_convert_encoding($body, 'utf-8', 'gbk');
// 获取页面id
            $sub = substr($body, strpos($body, 'postmessage_') + 12);
            $id = substr($sub, 0, strpos($sub, '"'));
            preg_match('/<span title="(.*?)">/',$body,$times);
            if (!preg_match('/\d+-\d+-\d+/',$times[1])){
                preg_match('/<em id="authorposton'.$id.'">发表于 (.*?)<\/em>/',$body,$times);
            }
            $time = strtotime($times[1]);

            $time = $time - 28800;

            
            // 匹配需要的内容
            preg_match("/<td class=\"t_f\" id=\"postmessage_{$id}\">(.*?)<\/td>/s", $body, $txt);

            $preg = array(
                '/\w+.jpg/', '/\d+.\d+ KB,/', '/\( 下载次数: \d+\)/', '/下载附件/', '/保存到相册/', '/上传/',
                '/\d+-\d+-\d+ \d+:\d+/', '/\( 下载次数: \d+, 售价: \d+ 点财富\)/', '/点击文件名/', '/售价: \d+ 点财富/',
                '/\w+.png/', '/\w+.gif/', '/\d+.\d+ MB,/', '/直播吧/', '/ET足球网/', '/<img.*?>/', '/灵犀足球网官方微信群：/',
                '/&nbsp;/','/\d+天前/','/昨天\d+:\d+/','/前天\d+:\d+/','/\d+小时前/','/\w+.jpeg/'
            );
            $replace = array('( 下载次数: 0)','<img id="aimg_h1LOo" onclick="zoom(this, this.src, 0, 0, 0)" class="zoom" src="http://www.hangxun100.com/wx.png" onmouseover="img_onmouseoverfunc(this)" onload="thumbImg(this)" border="0" alt="" />',
                '灵犀足球网(<a href="http://www.hangxun100.com/" target="_blank">www.hangxun100.com</a>)');
            $str = preg_replace($preg, '', $txt[1]);
            $str = str_replace($replace, '', $str);
            $str = str_replace('<br>','=替换=',$str);
            $str = strip_tags($str);
            $str = str_replace('=替换=','<br>',$str);
            // $content  正文内容
            $content = trim($str);


            // 在ID表中插入ID并获取用于本次新增
//                    $pid = 0;
            $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
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
//            exit();
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

// 英超  西甲   意甲   德甲
function FId($key)
{
    switch ($key) {
        case '0':
            return 46;
            break;
        case '1':
            return 48;
            break;
        case '2':
            return 47;
            break;
        case '3':
            return 49;
            break;
        default:
            return 50;
            break;
    }
}

function Check($key, $type="default")
{
    if ($key == 0)
        return YingChao($type);
    elseif ($key == 1)
        return XiJia($type);
    elseif ($key == 2)
        return YiJia($type);
    elseif ($key == 3)
        return DeJia($type);
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
            return 11;
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
            return 25;
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
            return 37;
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
            return 51;
    }
}
















