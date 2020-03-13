<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('http://www.hangxun100.com/forum-39-1.html');
$update_num = 0;
foreach ($haomen as $key => $item) {

    //获取列表页
    try {
        $html = file_get_contents($item);
    } catch (Exception $exception) {
        continue;
    }
//    $html = mb_convert_encoding($html, 'utf-8', 'gbk');

//拆分为数组
    $html = explode('</tbody>', $html);
//print_r($html);

    foreach ($html as $value) {
        //获取发布时间
        preg_match("/<span>(.*?)<\/span>/", $value, $contents);
//        exit();
        if (count($contents) == 0)
            preg_match("/<span class=\"xi1\">(.*?)<\/span>/", $value, $contents);
        if (preg_match("/<span>(.*?)<\/span>/", $value) || preg_match("/<span class=\"xi1\">(.*?)<\/span>/", $value)) {
            if (preg_match('/\d+-\d+-\d+ \d+:\d+/', $contents[1])) {
                //判断发布的时间是否为新
                $time = strtotime(str_replace("&nbsp;&nbsp;","",$contents[1]));
                $time_now = strtotime(date('Y-m-d H:i:s'));
                if ($time_now - $time < 172800  ) {
                       $time = $time - 28800;
//                print $value;
                    //进入内容页
                    preg_match_all('/<a href="(.*?)" onclick/', $value, $href);
                    $href = "http://www.hangxun100.com/".$href[1][1];
                    $body = file_get_contents($href);

                    // 获取页面id
                    $sub = substr($body, strpos($body, 'postmessage_') + 12);
                    $id = substr($sub, 0, strpos($sub, '"'));

                    // 匹配需要的内容
                    preg_match("/<td class=\"t_f\" id=\"postmessage_{$id}\">(.*?)<\/td>/s", $body, $txt);

                    $preg = array(
                        '/\w+.jpg/', '/\d+.\d+ KB,/', '/\( 下载次数: \d+\)/', '/下载附件/', '/保存到相册/', '/上传/',
                        '/\d+-\d+-\d+ \d+:\d+/', '/\( 下载次数: \d+, 售价: \d+ 点财富\)/', '/点击文件名/', '/售价: \d+ 点财富/',
                        '/\w+.png/', '/\w+.gif/', '/\d+.\d+ MB,/', '/直播吧/', '/ET足球网/', '/<img.*?>/', '/灵犀足球网官方微信群：/',
                        '/&nbsp;/',
                    );
                    $replace = array('( 下载次数: 0)','<img id="aimg_h1LOo" onclick="zoom(this, this.src, 0, 0, 0)" class="zoom" src="http://www.hangxun100.com/wx.png" onmouseover="img_onmouseoverfunc(this)" onload="thumbImg(this)" border="0" alt="" />',
                        '灵犀足球网(<a href="http://www.hangxun100.com/" target="_blank">www.hangxun100.com</a>)', '灵犀足球网(www.hangxun100.com)',
	'灵犀足球网(<a href="http://www.hangxun100.com" target="_blank">www.hangxun100.com</a>)');
                    $str = preg_replace($preg, '', $txt[1]);
                    $str = str_replace($replace, '', $str);
                    // $content  正文内容
                    $content = $str;
                    //匹配标题
                    preg_match('/<title>(.*?)- /', $body, $titles);
                    // $title 标题
                    $title = $titles[1];
                    // 以标题判断是否含有重复
                    $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
                    if ($bool > 0 || strpos($title,'回忆杀')!==false) {
                        continue;
                    }
                    // 在ID表中插入ID并获取用于本次新增
//                    $pid = 0;
                    $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
                    // 匹配类别 在$value中进行匹配
//                    preg_match('/<em>\[<a(.*?)<\/a>/', $value, $types);
//                    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $types[1], $cn_name);
                    // $type 类别
//                    $type = implode('', $cn_name[0]);
                    // 以类别获取类别ID
                    $type_id = 0;

                    // 获取Fid fid为栏目类型
                    $fid = 36;

                    //   开始赋值表内容   thread表   post表    post_tableid表（id表在进入开始就已）
                    //thread表内容开始
                    $data['fid'] = $fid;
                    $data['posttableid'] = 0;
                    $data['typeid'] = $type_id;
                    $data['sortid'] = 0;
                    $data['readperm'] = 0;
                    $data['price'] = 0;
                    $data['author'] = '视频版主';
                    $data['authorid'] = 2;
                    $data['subject'] = $title;
                    $data['dateline'] = $time;
                    $data['lastpost'] = $time;
                    $data['lastposter'] = '视频版主';
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

//                C::t('forum_post')->insert($article);
//                C::t('forum_thread')->insert($data);
                    DB::insert('forum_post', $article);
                    DB::insert('forum_thread', $data);
                    $update_num += 1;

                }


            }
        }
    }
}
if($update_num > 0){
    $todayposts =  DB::result_first("select todayposts from " . DB::table('forum_forum') . " where fid={$fid}");
    $todayposts += $update_num;
    DB::update('forum_forum', array('todayposts'=>$todayposts),array('fid'=> $fid));
}
