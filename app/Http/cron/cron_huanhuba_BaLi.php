<?php
set_time_limit(0);
ini_set("max_execution_time", 0);
$haomen = array('https://data.huanhuba.com/team/psg-18433-92-181-37503/');
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
    $body = explode('news-list',$html);
    $body = explode('u-card',$body[1]);
    $html = explode('</a>',$body[0]);
//print_r($html);

    foreach ($html as $value) {
        preg_match('/<p(.*?)<\/p>/',$value ,$titles);
        preg_match('/href="(.*?)"/',$value ,$hrefs);
//        print_r($titles);
//        print_r($hrefs);
//        print "-------------------------------------------".chr(10);
        if(count($titles)>0 && count($hrefs)>0){
            $title = strip_tags($titles[0]);
            // $title 标题
            // 以标题判断是否含有重复
            $bool = DB::result_first("SELECT count(*) FROM " . DB::table('forum_thread') . " WHERE subject='{$title}'");
            if ($bool > 0) {
                continue;
            }
            $body_html = file_get_contents($hrefs[1],false,stream_context_create($options));

            if (preg_match('/欢呼吧讯(.*)本场战报由欢呼吧足球AI智能编辑系统发布/',$body_html)){
                preg_match('/欢呼吧讯(.*)本场战报由欢呼吧足球AI智能编辑系统发布/',$body_html,$bodys);
                $body = $bodys[1];
            }else{
                $body = substr($body_html,strpos($body_html,'<div class="detail-content-box"'));
                $body = substr($body,0,strpos($body,'</div>')+6);
            }
            $body = str_replace(array('本场战报由欢呼吧足球AI智能编辑系统发布','欢呼吧'),'',$body);
            $body = preg_replace('/<a .*?>/','',$body);
            $body = str_replace('</a>','',$body);
            $content = $body;
            preg_match('/\d+-\d+-\d+ \d+:\d+:\d+/',$body_html,$times);
            $time = $times[0];
            $time= strtotime($time);

//            $time = $time- 28800;


            // 在ID表中插入ID并获取用于本次新增
            $pid = C::t('forum_post_tableid')->insert(array('pid' => null), true);
//            $pid = 0;
            // 以类别获取类别ID
            $type_id = 167;

            // 获取Fid fid为栏目类型
            $fid = 62;

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





