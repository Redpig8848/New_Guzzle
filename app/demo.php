<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('http://www.soccerbar.cc/forum-36-1.html', 'http://www.soccerbar.cc/forum-38-1.html',
    'http://www.soccerbar.cc/forum-35-1.html', 'http://www.soccerbar.cc/forum-39-1.html', 'http://www.soccerbar.cc/forum-43-1.html',
    'http://www.soccerbar.cc/forum-47-1.html', 'http://www.soccerbar.cc/forum-44-1.html', 'http://www.soccerbar.cc/forum-40-1.html',
    'http://www.soccerbar.cc/forum-189-1.html', 'http://www.soccerbar.cc/forum-37-1.html', 'http://www.soccerbar.cc/forum-50-1.html');


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
                    //   $time = $time - 28800;
//                print $value;
                    //进入内容页
                    preg_match('/<\/em> <a href="(.*?)" onclick/', $value, $href);
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
                    $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
                    // 匹配类别 在$value中进行匹配
                    preg_match('/<em>\[<a(.*?)<\/a>/', $value, $types);
                    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $types[1], $cn_name);
                    // $type 类别
                    $type = implode('', $cn_name[0]);
                    // 以类别获取类别ID
                    $type_id = Check($key, $type);

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
                    $data['author'] = '豪门足球版主';
                    $data['authorid'] = 5;
                    $data['subject'] = $title;
                    $data['dateline'] = $time;
                    $data['lastpost'] = $time;
                    $data['lastposter'] = '豪门足球版主';
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

                    // 加入表关系ID
                    $data['tid'] = $pid;
                    $article['tid'] = $data['tid'];
                    $article['pid'] = $data['tid'];
                    print_r($data);
//                C::t('forum_post')->insert($article);
//                C::t('forum_thread')->insert($data);
                    DB::insert('forum_post', $article);
                    DB::insert('forum_thread', $data);


                }


            }
        }
    }
}


function FId($key)
{
    switch ($key) {
        case '0':
            return 53;
            break;
        case '1':
            return 54;
            break;
        case '2':
            return 55;
            break;
        case '3':
            return 56;
            break;
        case '4':
            return 57;
            break;
        case '5':
            return 58;
            break;
        case '6':
            return 59;
            break;
        case '7':
            return 60;
            break;
        case '8':
            return 61;
            break;
        case '9':
            return 63;
            break;
        case '10':
            return 64;
            break;
        default:
            return 53;
            break;
    }
}

function Check($key, $type)
{
    if ($key == 0)
        return AC($type);
    elseif ($key == 1)
        return GuoJi($type);
    elseif ($key == 2)
        return YouWen($type);
    elseif ($key == 3)
        return BaiRen($type);
    elseif ($key == 4)
        return ManLian($type);
    elseif ($key == 5)
        return ASenNa($type);
    elseif ($key == 6)
        return QieErXi($type);
    elseif ($key == 7)
        return LiWuPu($type);
    elseif ($key == 8)
        return ManChen($type);
    elseif ($key == 9)
        return HuangMa($type);
    elseif ($key == 10)
        return BaSai($type);

}

//AC米兰吧
function AC($name)
{
    switch ($name) {
        case '其他':
            return 98;
            break;
        case '转载':
            return 99;
            break;
        case '视频':
            return 100;
            break;
        case '球员':
            return 101;
            break;
        case '原创':
            return 102;
            break;
        case '讨论':
            return 103;
            break;
        default:
            return rand(102, 103);
    }
}

//国际米兰吧
function GuoJi($name)
{
    switch ($name) {
        case '留言版':
            return 104;
            break;
        case '转载':
            return 105;
            break;
        case '视频':
            return 106;
            break;
        case '原创':
            return 107;
            break;
        case '其他':
            return 108;
            break;
        case '讨论':
            return 109;
            break;
        case '专题':
            return 110;
            break;
        case '球员':
            return 111;
            break;
        default:
            return rand(107, 109);
    }
}

//尤文图斯吧
function YouWen($name)
{
    switch ($name) {
        case '球员':
            return 112;
            break;
        case '原创':
            return 113;
            break;
        case '转载':
            return 114;
            break;
        case '留言版':
            return 115;
            break;
        case '新闻':
            return 116;
            break;
        case '尤文图斯':
            return 117;
            break;
        case '讨论':
            return 118;
            break;
        default:
            return 117;
    }
}

//拜仁慕尼黑吧
function BaiRen($name)
{
    switch ($name) {
        case '灌水':
            return 119;
            break;
        case '球员':
            return 120;
            break;
        case '视频':
            return 121;
            break;
        case '拜仁':
            return 122;
            break;
        case '讨论':
            return 123;
            break;
        case '原创':
            return 124;
            break;
        case '转载':
            return 125;
            break;
        default:
            return 122;
    }
}

//曼联吧
function ManLian($name)
{
    switch ($name) {
        case '灌水':
            return 126;
            break;
        case '原创':
            return 127;
            break;
        case '球员':
            return 128;
            break;
        case '曼联':
            return 129;
            break;
        case '新闻':
            return 130;
            break;
        case '转载':
            return 131;
            break;
        case '其他':
            return 132;
            break;
        case '讨论':
            return 133;
            break;
        default:
            return 129;
    }
}

//阿森纳吧
function ASenNa($name)
{
    switch ($name) {
        case '灌水':
            return 134;
            break;
        case '流言版':
            return 135;
            break;
        case '阿森纳':
            return 136;
            break;
        case '转载':
            return 137;
            break;
        case '讨论':
            return 138;
            break;
        case '其他':
            return 139;
            break;
        case '原创':
            return 140;
            break;
        default:
            return 136;
    }
}

//切尔西吧
function QieErXi($name)
{
    switch ($name) {
        case '切尔西':
            return 141;
            break;
        case '视频':
            return 142;
            break;
        case '流言版':
            return 143;
            break;
        case '原创':
            return 144;
            break;
        case '转载':
            return 145;
            break;
        case '灌水':
            return 146;
            break;
        case '其他':
            return 147;
            break;
        case '讨论':
            return 148;
            break;
        default:
            return 141;
    }
}

//利物浦吧
function LiWuPu($name)
{
    switch ($name) {
        case '灌水':
            return 149;
            break;
        case '原创':
            return 150;
            break;
        case '转载':
            return 151;
            break;
        case '利物浦':
            return 152;
            break;
        case '球员':
            return 153;
            break;
        case '流言版':
            return 154;
            break;
        case '讨论':
            return 155;
            break;
        case '其他':
            return 156;
            break;
        default:
            return 152;
    }
}

//曼城
function ManChen($name)
{
    switch ($name) {
        case '曼城':
            return 157;
            break;
        case '球员':
            return 158;
            break;
        case '流言版':
            return 159;
            break;
        case '原创':
            return 160;
            break;
        case '转载':
            return 161;
            break;
        case '灌水':
            return 162;
            break;
        case '其他':
            return 163;
            break;
        case '讨论':
            return 164;
            break;
        default:
            return 157;
    }
}

//巴黎圣日耳曼
function BaLi($name)
{
    switch ($name) {
        case '讨论':
            return 165;
            break;
        case '其他':
            return 166;
            break;
        case '巴黎':
            return 167;
            break;
        case '球员':
            return 168;
            break;
        case '流言版':
            return 169;
            break;
        case '转载':
            return 170;
            break;
        case '原创':
            return 171;
            break;
        default:
            return 167;
    }
}


//皇马马德里吧
function HuangMa($name)
{
    switch ($name) {
        case '讨论':
            return 172;
            break;
        case '其他':
            return 173;
            break;
        case '皇马':
            return 174;
            break;
        case '流言版':
            return 175;
            break;
        case '球员':
            return 176;
            break;
        case '转载':
            return 177;
            break;
        case '原创':
            return 178;
            break;
        default:
            return 174;
    }
}

//巴萨罗纳吧
function BaSai($name)
{
    switch ($name) {
        case '讨论':
            return 179;
            break;
        case '巴萨':
            return 180;
            break;
        case '其他':
            return 181;
            break;
        case '球员':
            return 182;
            break;
        case '转载':
            return 183;
            break;
        case '原创':
            return 184;
            break;
        case '流言版':
            return 185;
            break;
        default:
            return 180;
    }
}


