<?php
	require_once './common/Crawler.interface.php';
	require_once './common/functions.php';
	require_once './common/Sql.class.php';
	class Crawler5173 implements Crawler{
		private $urlArray;//存储url的二维数组，$urlArray[0][1]表示广东1区批发url
		private $lowPrice;//价格下限
		private $highPrice;//价格上限
		private $maxPage;//最多抓取多少页
		private $result;//三维数组$result[0][0]["price"]表示广东1去的价格
		private $usedTime;//抓取用时
		private $recordsAmount;
		private $multiAmount;//最大并发量
		private $area;//包含大区名称和编号对应的数组
		private $areaAmount;//大区的数量
		private $startArea;//开始大区编号
		private $endArea;//结束大区编号
		private $isSearchSales;//是否搜索批发订单
		private $storeAmount; //每次搜索存储的记录条数
		public function  __construct($startArea,$endArea){
			$config = parse_ini_file("./common/config.ini",true);//加载配置文件
			$this->urlArray 		= $config["5173"]["url"];
			$this->lowPrice 		= intval($config["5173"]["lowPrice"]);
			$this->highPrice 		= intval($config["5173"]["highPrice"]);
			$this->maxPage 			= intval($config["5173"]["maxPage"]);
			$this->recordsAmount 		= intval($config["5173"]["recordsAmount"]);
			$this->multiAmount 		= intval($config["5173"]["multiAmount"]);
			$this->storeAmount		= intval($config["5173"]["storeAmount"]);
			$this->isSearchSales 		= intval($config["5173"]["isSearchSales"]);


			$this->area 			= $config["common"]["area"];
			$this->areaAmount 		= intval($config["common"]["areaAmount"]);

			$this->startArea 		= $startArea;
			$this->endArea 			= $endArea;
			$this->result 			= array();
			$this->usedTime 		= 0;
		}
		//$wholeUrlArray[]=$this->urlArray[$urlIndex]."$low-$high-0-itemprice_asc-1-$page.shtml";
		private function getUrl($url,$lowPrice,$highPrice,$page,$type){//$type=1 平常，$type=2 批发
			if($type==1){
				return $url."-0-bx1xiv-0-0-0-a-a-a-"."$lowPrice-$highPrice-0-itemprice_asc-1-$page.shtml";
			}else{
				return $url."-0-3juw0c-0-0-0-a-a-a-"."$lowPrice-$highPrice-0-itemprice_asc-1-$page.shtml";
			}
		}
		public function getAreaAmount(){
			return $this->areaAmount;
		}
		public function getAreaNumber($areaName){
			foreach($this->area as $k=>$v){
				if($areaName==$v)return $k;
			}
		}
		public function getInfoByPage($webContents){
			if(substr_count($webContents,"游戏币批发")>1){//判断出售类型
				$type = 2;
			}
			else $type = 1;
			$r = array();
			//商品总页数
			$areaName = $this->getAreaName($webContents);
			$totalAmount = $this->getTotalAmount($webContents);
			$currentPage = $this->getCurrentPage($webContents);
			$pattern = '/<div class="sin_pdlbox"[\d\D]{1,3000}<\/div>/';
			preg_match_all($pattern,$webContents,$match);
			if(!is_array($match)&&!isset($match[0])){
				exit();
			}
			//$i=0;
			foreach($match[0] as $key=>$v){
				$tmp_arr = array();
				preg_match('/<ul class="pdlist_price">[\d\D]{1,100}<\/ul>/',$v,$price);
				preg_match('/<li class="tt">[\d\D]{100,600}<\/li>/',$v,$goldAmount);
				preg_match('/<ul class="pdlist_num">[\d\D]{1,100}<\/ul>/',$v,$itemAmount);
				preg_match('/<li class="credit">[\d\D]{50,500}<\/li>/',$v,$credit);
				preg_match('/<ul class="pdlist_delivery">[\d\D]{10,2000}<\/ul>/',$v,$tmp);
				preg_match('/href="[\d\D]*"/',$tmp[0],$buyLink);
				if(is_array($price)&&isset($price[0])){
					$price = (int)(strip_tags($price[0]));
					$tmp_arr["price"] = $price;
					if(isset($goldAmount[0])&&$goldAmount[0]){
						$goldAmount = (int)(strip_tags($goldAmount[0]));
					}else{
						exit();
					}
						
					$tmp_arr["goldAmount"] = $goldAmount;
			
					$itemAmount = (int)(strip_tags($itemAmount[0]));
			
					$tmp_arr["itemAmount"] = $itemAmount;
			
					$credit = substr($credit[0],strpos($credit[0],"title")+7,50);
			
					if(strpos($credit,"0")==false){
						$tmp_arr["credit"] = "暂无";
					}else{
							
						$tmp_arr["credit"] = substr($credit,0,4);
					}
					$buyLink = substr($buyLink[0],strpos($buyLink[0],"href=")+6);
					$buyLink = substr($buyLink,0,strpos($buyLink,'"'));
					$tmp_arr["buyLink"] = $buyLink;
					if($type==2)$tmp_arr["sale"] = 1;//表示批发
					else $tmp_arr["sale"] = 0;//表示零售
					
					if(strpos($v,"您将获得10元赔款"))$tmp_arr["payFor"] = 1;//表示赔付
					else $tmp_arr["payFor"] = 0;
					
					if(strpos($v,"可获免单机会"))$tmp_arr["free"] = 1;//表示免单
					else $tmp_arr["free"] = 0;

					if(strpos($v,"附魔")){
						$tmp_arr["magic"] = 1;
						//echo $v;
					}//表示附魔
					else $tmp_arr["magic"] = 0;
					
					if(substr_count($v,"danbao.5173.com")>1)$tmp_arr["ensure"] = 1;//表示担保
					else $tmp_arr["ensure"] = 0;
					
					if(substr_count($v,"consignment.5173.com")>1)$tmp_arr["consign"] = 1;//表示寄售
					else $tmp_arr["consign"] = 0;

					//echo $v;
					$tmp_arr["time"] = date("Y-m-d H:i:s");
					$tmp_arr["platform"] = "5173";
					$r[] = $tmp_arr;
				}
				
		}
		$arr = array("contents"=>$r,"areaNumber"=>($this->getAreaNumber($areaName)),"currentPage"=>$currentPage,"totalAmount"=>$totalAmount,"type"=>$type);//1表示正常单价，2表示批发
		return $arr;
	}
		private function getAreaName($webContent){
			preg_match('/<title>[\d\D]+-5173.com<\/title>/',$webContent,$area);
			if(isset($area)&&!empty($area[0]))
				return substr($area[0],strpos($area[0]," ")+1,strpos($area[0],"-")-strpos($area[0]," ")-1);
			else return false;
		}
		private function getCurrentPage($webContent){
			preg_match('/currentPage:\d+/',$webContent,$currentPage);
			//print_r($pageAmount);
			//exit();
			if(isset($currentPage)&&!empty($currentPage[0]))
				return (int)(substr($currentPage[0],strpos($currentPage[0],":")+1));
			else return false;
		}
		private function getTotalAmount($webContent){
			preg_match('/totalAmount:\d+/',$webContent,$pageAmount);
			//print_r($pageAmount);
			//exit();
			if(isset($pageAmount)&&!empty($pageAmount[0]))
				return (int)(substr($pageAmount[0],strpos($pageAmount[0],":")+1));
			else return false;
		}

		public function start(){
			$startTime = time();
			if($this->lowPrice<0)$this->lowPrice=0;
			if($this->highPrice<0)$this->highPrice=0;
			$low=min($this->lowPrice,$this->highPrice);
			$high=max($this->lowPrice,$this->highPrice);
			$pageCount = $this->maxPage;
			$res = array();
			$wholeUrlArray = array();
			$page = 1;//当前页数
			for($urlIndex = $this->startArea;$urlIndex<$this->endArea&&$urlIndex<$this->areaAmount;$urlIndex++){
				$wholeUrlArray[] = $this->getUrl($this->urlArray[$urlIndex], $low, $high, $page, 1);
				if($this->isSearchSales) {
					$wholeUrlArray[] = $this->getUrl($this->urlArray[$urlIndex], $low, $high, $page, 2);
				}
			}
			
			$contents = array();
			for($i=0;$i<count($wholeUrlArray);$i=$i+$this->multiAmount){
				$contents = array_merge($contents,multi_get_contents(array_slice($wholeUrlArray,$i,$this->multiAmount)));
				
			}-
			//$this->storageWebpage($contents,"./page");
			$index = 0;
			$areaPageAmount = array();
			
			while($index<count($contents)){
				$r = $this->getInfoByPage($contents[$index++]);
				//$arr = array("contents"=>$r,"areaNumber"=>($this->getAreaNumber($areaName)),"currentPage"=>$currentPage,"totalAmount"=>$totalAmount,"type"=>$type);//1表示正常单价，2表示批发
				if(!isset($areaPageAmount[$r["areaNumber"]])){
					$areaPageAmount[$r["areaNumber"]] = array();
					$areaPageAmount[$r["areaNumber"]][$r["type"]] = $r["totalAmount"];
				}
				else{
					$areaPageAmount[$r["areaNumber"]][$r["type"]] = $r["totalAmount"];
				}
				$res[] = $r;
			}
			$wholeUrlArray = array();
			foreach ($areaPageAmount as $k => $v){
				foreach ($v as $kk => $vv){
					for($i=2;$i<=$vv&$i<=$this->maxPage;$i++){
						$wholeUrlArray[] = $this->getUrl($this->urlArray[$k], $low, $high, $i, $kk);
					}
					
				}
			}

			$contents = array();
			for($i=0;$i<count($wholeUrlArray);$i=$i+$this->multiAmount){
				$contents = array_merge($contents,multi_get_contents(array_slice($wholeUrlArray,$i,$this->multiAmount)));
			
			}
			//$this->storageWebpage($contents,"./page");
			$index = 0;
			while($index<count($contents)){
				$r = $this->getInfoByPage($contents[$index++]);
				$res[] = $r;
			}
			
			foreach ($res as $k => $v){
				if(isset($this->result[$v["areaNumber"]])){
					$this->result[$v["areaNumber"]] = array_merge($this->result[$v["areaNumber"]],$v["contents"]);
				}else{
					$this->result[$v["areaNumber"]] = $v["contents"];
				}
					
			}
			$res = array();
			foreach ($this->result as$k => $v){
				usort($v,"cmpByUnivalenceDesc");
				$res [$k] = $v; 
			}
			$this->result = $res;
			$endTime = time();
			$this->usedTime = $endTime - $startTime;
		}
		
		private function storageWebpage($c,$path){
			if(is_dir($path)){
				removeDir($path);
			}
			mkdir($path,0777);
			for($i=0;$i<count($c);$i++){
				file_put_contents($path."/$i.html",$c[$i]);
			}
		}
		public function store(){
			//if(count($this->result)!=$this->areaAmount)return false;
			$DB = new Sql();

			foreach ($this->result as $index => $r){
				$count = 0;
				foreach($r as $K => $v){
					if($count++ >= $this->storeAmount)break;
					$areaNumber = $index;
					
					$univalence 	= number_format($v["goldAmount"]/$v["price"],2);
					$goldAmount 	= $v["goldAmount"];
					$price 		    = $v["price"];
					$itemAmount 	= $v["itemAmount"];
					$ensure 	    = $v["ensure"];
					$consign 	    = $v["consign"];
					$sale 		    = $v["sale"];
					$payFor 	    = $v["payFor"];
					$free 		    = $v["free"];
					$magic 		    = $v["magic"];
					$platform 	    = $v["platform"];
					$credit 	    = $v["credit"];
					$time 		    = $v["time"];
					$buyLink	    = $v["buyLink"];
//					$urlInfo 	= parse_url($v["buyLink"]);
//					$queryArray = explode("&", $urlInfo["query"]);
//					$order		= explode("=", $queryArray[0]);
//					$orderID	= $order[1];
		
//                    $selectSql	    = "select buyLink from" . " record_5173_" . "$areaNumber where buyLink = '$buyLink'";
//					$res = $DB->dql($selectSql);
//                    if(count($res) > 0)continue;
                    
					$storeSql 	    = "insert into record_5173_"."$areaNumber(
					univalence,
					goldAmount,
					price,
					itemAmount,
					ensure,
					consign,
					sale,
					payfor,
					free,
					magic,
					platform,
					credit,
					createTime,
					buyLink
					) 
					values(
					$univalence,
					$goldAmount,
					$price,
					$itemAmount,
					$ensure,
					$consign,
					$sale,
					$payFor,
					$free,
					$magic,
					'$platform',
					'$credit',
					'$time',
					'$buyLink'
					)";
					//echo $sql."<br />";
					$dml = $DB->dml($storeSql);
					//var_dump($dql);
				}	
			}	
		}
	public function showList(){
		if($this->result==null)return;
		else $result = $this->result;
		echo "<h1 align='center'>总共".count($this->result)."个区</h1>";
		foreach ($result as $k => $r){
			if(empty($r))continue;
			echo "<table border=1 align='center'>";
			$areaNumber = $k + 1;
			echo "<caption><b>第{$areaNumber}区:".$this->area[$k]."</b> total:<b>".count($r)."</b> records,used time:<b>".$this->usedTime."</b> seconds</caption>";
			echo "<th>编号</th><th>比例</th><th>数量</th><th>价格</th><th>件数</th><th>担保</th><th>寄售</th><th>批发</th><th>赔付</th><th>免单</th><th>附魔</th><th>平台</th><th>信用</th><th>时间</th>";
			echo "<th>购买链接</th>";
			$count = 1;
			foreach ($r as $v) {
				echo "<tr width='200px'align='center'>";
				echo "<td width='30px'>".$count++."</td>";
				echo "<td width='100px'>".number_format($v["goldAmount"]/$v["price"],2)."</td>";
				echo "<td width='100px'>".$v["goldAmount"]."</td>";
				echo "<td width='100px'>".$v["price"]."</td>";
				echo "<td width='100px'>".$v["itemAmount"]."</td>";
				echo "<td width='30px'>".$v["ensure"]."</td>";
				echo "<td width='30px'>".$v["consign"]."</td>";
				echo "<td width='30px'>".$v["sale"]."</td>";
				echo "<td width='30px'>".$v["payFor"]."</td>";
				echo "<td width='30px'>".$v["free"]."</td>";
				echo  $v["magic"]==0 ? "<td width='30px'>".$v["magic"]."</td>" : "<td width='30px' bgcolor='red'>".$v["magic"]."</td>";
				echo "<td width='100px'>".$v["platform"]."</td>";
				echo "<td width='100px'>".$v["credit"]."</td>";
				echo "<td width='220px'>".$v["time"]."</td>";
				echo "<td width='200px'><a href=".'"'.$v["buyLink"].'">'."buy"."</a></td>";
				//echo "<td width='220px'>".$v["totalAmount"]."</td>";
				if($count>$this->recordsAmount)break;
			}
			echo "</table>";

		}
	
	}
}
function cmpByUnivalenceDesc($a,$b){
	return ($a["goldAmount"] / $a["price"])<($b["goldAmount"] / $b["price"]);
}
