<?php
function test5173(){
	$config = parse_ini_file("./common/config.ini",true);//���������ļ�
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
 * cURL multi��������
 * 
 * @author mckee
 * @link http://www.111cn.net
 * 
 *
 */
//ǿ��ɾ���ļ�
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
//����һ������url��һά����
function multi_get_contents($url_array){
	$handles = $contents = array(); 
	 
	//��ʼ��curl multi����
	$mh = curl_multi_init();
	 
	//���curl ������Ự
	foreach($url_array as $key => $url)
	{
	    $handles[$key] = curl_init($url);
	    curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($handles[$key], CURLOPT_TIMEOUT, 10);
	    curl_multi_add_handle($mh, $handles[$key]);
	}
	 
	//======================ִ����������=================================
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
	 
	//��ȡ����������
	foreach($handles as $i => $ch)
	{
	    $content = curl_multi_getcontent($ch);
	    $contents[$i] = curl_errno($ch) == 0 ? $content : '';
	}
	 
	//�Ƴ���������
	foreach($handles as $ch)
	{
	    curl_multi_remove_handle($mh, $ch);
	}
	 
	//�ر���������
	curl_multi_close($mh);
	return $contents;
}

