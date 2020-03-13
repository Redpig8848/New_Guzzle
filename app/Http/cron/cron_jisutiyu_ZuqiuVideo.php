<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('http://www.jisutiyu.com/football/all/'
);
$update_num = 0;
foreach ($haomen as $key => $item) {

    //获取列表页
    try {
        $html = file_get_contents($item);
    } catch (Exception $exception) {
        continue;
    }
    $html = mb_convert_encoding($html, 'utf-8', 'gbk');

//拆分为数组
    $html = explode('<li>', $html);
//print_r($html);

    foreach ($html as $value) {
        //获取发布时间

        if (preg_match('/<a href="(.*?)" target="_blank" class=/',$value,$href)) {
            preg_match('/<a href="\/(.*?)<\/a>/', $value, $title);
            $title = strip_tags($title[0]);
            // $title 标题

            // 以标题判断是否含有重复
            $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
            if ($bool > 0) {
                break;
            }


//                print $value;
            //进入内容页
//                    print $href[1];
            $body = file_get_contents("http://www.jisutiyu.com".$href[1]);
            $body = mb_convert_encoding($body, 'utf-8', 'gbk');

            // 匹配需要的内容
            $sub_num =  strpos($body,'<div id="zhichi">');
            $sub = substr($body,$sub_num+17);
            $sub_length = strpos($sub,'<div');
            $content = substr($sub,0,$sub_length);
            $content = str_replace('</div>','',$content);
            if (strlen($content)<10){
                $sub_num =  strpos($body,'<div id="zhichi">');
                $sub = substr($body,$sub_num+17);
                $sub_length = strpos($sub,'</div>');
                $content = substr($sub,0,$sub_length+6);
            }

            preg_match('/<\/small>(.*?)<small>/', $body, $time);
            $time = strtotime($time[1]);
              $time = $time - 28800;

            // 在ID表中插入ID并获取用于本次新增
//                    $pid = 0;
            $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
            // 匹配类别 在$value中进行匹配
//            preg_match('/<em>\[<a(.*?)<\/a>/', $value, $types);
//            preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $types[1], $cn_name);
            // $type 类别
//            $type = implode('', $cn_name[0]);
            // 以类别获取类别ID
            $type_id = 0;

            // 获取Fid fid为栏目类型
            $fid = 2;

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
//                    print_r($data);
//                    exit();
//                C::t('forum_post')->insert($article);
//                C::t('forum_thread')->insert($data);
            DB::insert('forum_post', $article);
            DB::insert('forum_thread', $data);
            $update_num += 1;

        }
    }
}

if($update_num > 0){
    $todayposts =  DB::result_first("select todayposts from " . DB::table('forum_forum') . " where fid={$fid}");
    $todayposts += $update_num;
    DB::update('forum_forum', array('todayposts'=>$todayposts),array('fid'=> $fid));
}

