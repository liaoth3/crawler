<?php
function test5173(){
	$config = parse_ini_file("./common/config.ini",true);//加载配置文件
	$startArea 	= intval($config["5173"]["startArea"]);
	$endArea 	= intval($config["5173"]["endArea"]);
	$multiAmount 	= intval($config["5173"]["multiAmount"]);
	$start 		= $startArea;
	do{
		$end = $start + $multiAmount;
		$c = new Crawler5173($start,$end);
		if($start > $endArea)break;
		$c->start();
		$c->store();
		//$c->showList();
		$start += $multiAmount;
	}while(1);
}
/**
 * cURL multi批量处理
 * 
 * @author mckee
 * @link http://www.111cn.net
 * 
 *
 */
//强制删除文件
function removeDir($dirName){
	if(!is_dir($dirName))return false;
	
	$handle =@opendir($dirName);
	while($file=readdir($handle)){
		if($file!='.'&&$file!='..'){
			$fileName = $dirName."/".$file;
			is_dir($fileName) ? removeDir($fileName):@unlink($fileName);
		}
	} 
	closedir($handle);
	return rmdir($dirName);
}
//传递一个包含url的一维数组
function multi_get_contents($url_array){
	$handles = $contents = array(); 
	 
	//初始化curl multi对象
	$mh = curl_multi_init();
	 
	//添加curl 批处理会话
	foreach($url_array as $key => $url)
	{
	    $handles[$key] = curl_init($url);
	    curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($handles[$key], CURLOPT_TIMEOUT, 10);
	    curl_multi_add_handle($mh, $handles[$key]);
	}
	 
	//======================执行批处理句柄=================================
	$active = null;
	do {
	    $mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	 
	 
	while ($active and $mrc == CURLM_OK) {
	    
	    if(curl_multi_select($mh) === -1){
	        usleep(100);
	    }
	    do {
	        $mrc = curl_multi_exec($mh, $active);
	    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
	 
	}
	 
	//获取批处理内容
	foreach($handles as $i => $ch)
	{
	    $content = curl_multi_getcontent($ch);
	    $contents[$i] = curl_errno($ch) == 0 ? $content : '';
	}
	 
	//移除批处理句柄
	foreach($handles as $ch)
	{
	    curl_multi_remove_handle($mh, $ch);
	}
	 
	//关闭批处理句柄
	curl_multi_close($mh);
	return $contents;
}

