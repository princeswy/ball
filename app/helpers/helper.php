<?php 

use Illuminate\Support\Facades\Config;

/**
 * http 相关函数库
 */
/**
 * curl get contents
 */
if(! function_exists('curl_file_get_contents')){
	function curl_file_get_contents($durl,$tm=15){
	    $i = 0;
	    while($i++ < 5){
	        $content = curl_file_contents($durl,$tm);
	        if( $content ) {
	            return $content;
	        }
	        if($content==0) break;
	        usleep(100000);//wait 0.1s
	    }
	    return false;
	}
}

/**
 * 
 */
if(! function_exists('killbom')){
	function killbom($text){
	        return preg_replace('/^(\xef\xbb\xbf)/','',$text);
	}
}

if(! function_exists('success')){
    function success(){
        return 10000;
    }
}

if(! function_exists('get_ret_code')){
    function get_ret_code($type='success'){

        $code_map = [
            'success' => 10000,
            'failed' => 10001,
        ];
        return $code_map[$type];
    }
}

/**
 * curl http url
 */
if(! function_exists('curl_file_contents')){
	function curl_file_contents($durl,$tm=5,$ispost=false,$data='',$user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)",$referer=""){
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $durl);
	        curl_setopt($ch, CURLOPT_TIMEOUT, $tm);
	        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	        curl_setopt($ch, CURLOPT_REFERER,$referer);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLINFO_HEADER_OUT,1);            
            if($ispost){
                curl_setopt($ch, CURLOPT_POST, $ispost);
                if(is_array($data)){
                	$params = http_build_query($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }else{
                    $headers = array(
                        "Content-Length: ".strlen($data)." \r\n",
                        $data
	                );
	               //curl_setopt($ch, CURLOPT_HEADER, TRUE);  
                   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置http头 */
               }
            }
	        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);//301或302跳转
	        $r = curl_exec($ch);
	        $infos = curl_getinfo($ch);
	        //print_r($r);
	        if( (int)$infos['http_code'] != 200 ){
	            $log = ['error' => curl_error($ch),'errno' => curl_errno($ch),'exec' => $r, 'info' => $infos,];
	            write_log('http_error',$log,true);
	            return false;
	        } 
	        curl_close($ch);
	        return $r;
	}
}

// --------------------------------------------------------------------
/**
 * array_column
 *
 * Return array
 *
 * @access	public
 * @param	array
 * @param	array
 * @param	mixed
 * @return	mixed	depends on what the array contains
 */
if(!function_exists('array_column')){
    function array_column($input, $columnKey, $indexKey=null){
        $columnKeyIsNumber      = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull         = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber       = (is_numeric($indexKey)) ? true : false;
        $result                 = array();
        foreach((array)$input as $key=>$row){
            if($columnKeyIsNumber){
                $tmp            = array_slice($row, $columnKey, 1);
                $tmp            = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            }else{
                $tmp            = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if(!$indexKeyIsNull){
                if($indexKeyIsNumber){
                    $key        = array_slice($row, $indexKey, 1);
                    $key        = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key        = is_null($key) ? 0 : $key;
                }else{
                    $key        = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key]       = $tmp;
        }
        return $result;
    }
}


/**
 * 二维数组去重
 * @param	array
 * @return Return array
 */
if(!function_exists('arrays_unique')){
    function arrays_unique($array){
        if(empty($array)){
            return [];
        }
        foreach ($array as $v){
            $v = json_encode($v);
            $temp[] = $v;
        }
        $temp = array_unique($temp);
        foreach ($temp as $k => $v){
            $temp[$k] = json_decode($v,true);
        }
        return $temp;
    }
}


/**
 * 根据某个二维数组的key比较俩个数组的差集
 * @param	array
 * @param	array
 * @param	string
 * @return  array
 */
if(!function_exists('array_diff_deep')){
    function array_diff_deep($array1, $array2 ,$key) {
        $ret = array();
        $array1_column = array_column($array1, $key);

        $array2_column = array_column($array2, $key);

        $diff = array_diff($array1_column, $array2_column);
        if(empty($diff)){
            return array();
        }
        $ret = array_intersect_key($array1, $diff);
        return $ret;
    }
}

/**
 * 二维数组根据某些列 去重
 * @param	array
 * @param	array
 * @return Return array
 */
if(!function_exists('arrays_unique_key')){
    function arrays_unique_key($array,$key_array){
        foreach ($array as $id_key=>$v){
            $g =array();
            foreach($key_array as $str_key){
                $g["$str_key"] = $v["$str_key"];
            }
            $g = json_encode($g);
            $temp[$id_key] = $g;
        }
        $temp = array_unique($temp);

        $ret = array_intersect_key($array, $temp);


        return $ret;
    }
}

/**
 * 二维数组比较差集
 * @param	array
 * @param	array
 * @return Return array
 */
if(!function_exists('array_intersect_deep')){
    function array_intersect_deep($array1, $array2) {
        $temp1 = $temp2 = [];
        foreach ($array1 as $key=>$val){
            ksort($val);
            $temp1[$key] = json_encode($val);
        }
        foreach ($array2 as $key=>$val){
            ksort($val);
            $temp2[$key] = json_encode($val);
        }
         
        $diff = array_diff($temp1 , $temp2);
        $ret = array_intersect_key($array1, $diff);
        return $ret;
    }
}

/**
 * 
 */
if(!function_exists('_foreach_data')){
    function _foreach_data($toeach_data){
    
        if(!isset($toeach_data[0]) || count($toeach_data) == count($toeach_data, 1)){
            $data[] = $toeach_data;
        }else{
            $data = $toeach_data;
        }
    
        return $data;
    }
}
if(!function_exists('reset_key')){
    function reset_key($data,$rkey){
        if(empty($data) || !$rkey){
            return array();
        }
        foreach ($data as $key=>$val){
            $result[$val[$rkey]] = $val;
        }
        $data = null;
        return $result;
    }
}
if(!function_exists('check_process_num')){
    function check_process_num($script_name) {
        $cmd = @popen("ps -ef | grep '{$script_name}' | grep -v grep | wc -l", 'r');
        $num = (int) @fread($cmd, 512);
        (int) $num += 0;
        @pclose($cmd);
        if ($num > 1) {
            return false;
        }
        return true;
    }
}
 
/**
 *
 */
if(!function_exists('pp')){
    function pp($expression){
        return print_r($expression);
    }
}

if(!function_exists('vv')){
    function vv($expression){
        return var_dump($expression);
    }
}
/**
 * 
 */
//----------------------------------------------------------------------------

if(! function_exists('Newjapi_userLogin')){
    function Newjapi_userLogin($sessionid='',$param){
        $info = 'center/login';
        $data = Newjapi_request($param,$sessionid,$info);
        return json_decode($data,true);
    }
}

if (!function_exists('Newjapi_request')) {
    //提交到java
    function Newjapi_request($post_data, $sessionid = '', $info)
    {
        $Url = "http://mapi.yjcp.com/" . $info . "?";
        $o = '';
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $ch = curl_init();//初始化curl
        if ($sessionid) {
            $header[] = 'cookie:JSESSIONID=' . $sessionid;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_URL, $Url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}

if(! function_exists('JApi_get_userinfo')){
    /*
     * 获取用户信息（1104）
     * @param int $uid  用户id
     * @return array
     */
    function JApi_get_userinfo($sessionid,$uid) {
        $uid = intval($uid);
        if(empty($uid))
            return false;
        $param = array();
        $param[0] = 1104;
        $param[1] = $uid;
        $data = JApi_request($param,$sessionid);
        if(!$data)
            return false;
        $ParamList = explode('|',$data);
        if($ParamList[2]!=1)
            return false;
        $userinfo = json_decode($ParamList[3],true);
        $userinfo = $userinfo['list'];
        return $userinfo;
    }
}


if(! function_exists('JApi_get_betlist')){
    /*
     * 投注记录（新姚记悠彩）（1026）
     * @param int $uid  用户id
     * @param int $lotid  彩种id
     * @param int $pagesize  每页显示条数
     * @param int $currentpage  当前页数
     * @param int $isprize  中奖标识（0：全部 1：中奖；2：待开奖；3：我的追号）
     * @return array
     */
    function JApi_get_betlist($sessionid,$uid,$lotid,$pagesize=20,$currentpage=1,$isprize) {
        if(empty($uid))
            return false;
        $param = array();
        $param[0] = 1026;
        $param[1] = '';
        $param[2] = $lotid;
        $param[3] = $pagesize;
        $param[4] = $currentpage;
        $param[5] = $uid;
        $param[6] = $isprize;
        $data = JApi_request($param,$sessionid);
        if(!$data){
            return false;
        }
        $ParamList = explode('|',$data);
        $flaginfo = json_decode($ParamList[2],true);
//         {
//             $data['usableMoney'] = $flaginfo['usableMoney'];					//现金余额
//             $data['usableBonusMoney'] = $flaginfo['usableBonusMoney'];			//红包余额
//             $data['freezMoney'] = $flaginfo['freezMoney'];						//冻结资金
//         }
        $data1['lotType'] = $ParamList[3];						//彩种
        $data1['totalPages'] = $ParamList[4];					//总页数
        $data1['currentpage'] = $ParamList[5];					//当前页
        $betList = json_decode($ParamList[6],true);
        $data1['betList'] = $betList['list'];					//投注记录
        return $data1;
    }
}
//所有的优惠卷
if(! function_exists('Newjapi_coupon')){
	function Newjapi_coupon($sessionid,$param) {
		/*if(empty($userid))
		 return false;*/
		$info = 'coupon/usable';
		$data = Newjapi_request($param,$sessionid,$info);
		return json_decode($data,true);
	}
}

//各个彩种投注的优惠劵
if(! function_exists('Newjapi_coupon_cz')){
	function Newjapi_coupon_cz($sessionid,$param) {
		/*if(empty($userid))
		 return false;*/
		$info = 'coupon/bet/usable';
		$data = Newjapi_request($param,$sessionid,$info,$tab='4');
		return json_decode($data,true);
	}
}

//获取用户信息
if(! function_exists('Newjapi_getuserinfo')){
	function Newjapi_getuserinfo($sessionid,$param){
		$info = 'center/gain/userInfo';
		$data = Newjapi_request($param,$sessionid,$info,$tad=3);//TODO
		return json_decode($data,true);
	}
}


if(! function_exists('send_smg')){
    function send_smg($mobile,$content) {
        $content = urlencode($content);
        $postUrl = "http://10.16.10.25:8020/sendMsg?sendType=qf&content=$content&mobile=$mobile";
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 1);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }
}


/*
 //将日期转为星期
 */
if(! function_exists('wk')){
    function  wk($date1) {
        $year = substr($date1,0,4);       //获取年份
        $month = sprintf('%02d',substr($date1,4,2));  //获取月份
        $day = sprintf('%02d',substr($date1,6,2));      //获取日期
        $hour = $minute = $second = 0;   //默认时分秒均为0
        $dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
        $shuchu = date("w",$dayofweek);      //获取星期值
        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        return $weekarray[$shuchu].substr($date1,8,3);
    }
}


if(! function_exists('week')){
    function  week($date1) {
        $year = substr($date1,0,4);       //获取年份
        $month = sprintf('%02d',substr($date1,4,2));  //获取月份
        $day = sprintf('%02d',substr($date1,6,2));      //获取日期
        $hour = $minute = $second = 0;   //默认时分秒均为0
        $dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
        $shuchu = date("w",$dayofweek);      //获取星期值
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        return $weekarray[$shuchu];
    }
}

//八方预测比赛剩余时间
if(! function_exists('time2string')){
	function time2string($second)
		{
			$day = floor($second / (3600 * 24));
			$second = $second % (3600 * 24);//除去整天之后剩余的时间
			$hour = floor($second / 3600);
			$second = $second % 3600;//除去整小时之后剩余的时间
			$minute = floor($second / 60);
			$second = $second % 60;//除去整分钟之后剩余的时间
			//返回字符串
			$h = $day * 24 + $hour;
			if (intval($h) >= 100)
			{
				return $day . '天' . $hour . '时' . $minute . '分';
			}
			else
			{
				return $h . '时' . $minute . '分';
			}
			
		}
}
//获取静态资源路径
if(! function_exists('get_static_path')){
	function get_static_path($filepath){
		$path = Config::get('app.page_url');
		$path .= $filepath;
		$path .= "?v=".filemtime(base_path().'/../pages_web/'.$filepath);
		return $path;
	}
}

if(! function_exists('get_static_minify')){
	function get_static_minify($files, $dir='js/laravel_js'){
		$a = microtime();
		foreach (explode(',', $files) as $file){
			$filetime += filemtime(base_path()."/../pages_web/$dir/$file");
		}
		$path = Config::get('app.page_url')."/min/?b=$dir&f=";
		$path .= $files;
		$path .= "&v=$filetime";
		return $path;
	}
}

if(! function_exists('getMonthLastDay')){
    //获取 某个月的最大天数（最后一天）
    function getMonthLastDay($month,$year) {
        switch ($month) {
            case 4 :
            case 6 :
            case 9 :
            case 11 :
                $days = 30;
                break;
            case 2 :
                if ($year % 4 == 0) {
                    if ($year % 100 == 0) {
                        $days = $year % 400 == 0 ? 29 : 28;
                    } else {
                        $days = 29;
                    }
                } else {
                    $days = 28;
                }
                break;
            default :
                $days = 31;
                break;
        }
        return $days;
    }
}

//将日期转为星期
if(! function_exists('weekdays')){
    function weekdays($date1) {
        $datearr = explode("-",$date1);     //将传来的时间使用“-”分割成数组
        $year = $datearr[0];       //获取年份
        $month = sprintf('%02d',$datearr[1]);  //获取月份
        $day = sprintf('%02d',$datearr[2]);      //获取日期
        $hour = $minute = $second = 0;   //默认时分秒均为0
        $dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
        $shuchu = date("w",$dayofweek);      //获取星期值


        $weekarray = [ "周日","周一","周二","周三","周四","周五","周六"];
        $weeknum=array("周日"=>"0","周一"=>"1","周二"=>"2","周三"=>"3","周四"=>"4","周五"=>"5","周六"=>"6");
        return $weekarray[$shuchu];
    }

}


if(! function_exists('get_prc_time')){
	/**
	 * 时间格式 2017-02-27T17:22:00.908+01:00
	 * @param  $tz_time
	 */
	function get_prc_time($tz_time){
		$timezone = date_default_timezone_get();
		
		if($timezone != 'Asia/Shanghai'){
			date_default_timezone_set('Asia/Shanghai');
			$time = date('Y-m-d H:i:s', strtotime($tz_time));
			date_default_timezone_set($timezone);
				
		}else{
			$time = date('Y-m-d H:i:s', strtotime($tz_time));
				
		}
		
		return $time;
	}
}


if(! function_exists('convert_cetTime')){
    function convert_cetTime($cet_date) {
        date_default_timezone_set('CET');

        $is_cest = date('I');
        $date = date('Y-m-d H:i:s', strtotime($cet_date) + ($is_cest ? 6*3600 : 7*3600) );

        date_default_timezone_set('Asia/Shanghai');
        return $date;
    }
}

if(!function_exists('write_log')){

    function write_log($type,$content){
        if (empty($type) || !isset($content)) {
            return false;
        }
        if(is_object($content)){
            $content = var_export($content,true);
        } else {
            isset($content['match_id']) && $data['match_id'] = $content['match_id'];
            is_string($content) && $data['match_id'] = json_decode($content,true)['match_id'];
            $content = is_array($content) ? var_export($content,true) : $content;
        }
        
        $data['type'] = $type;
        $data['data'] = $content;
        $data['time'] = date('Y-m-d H:i:s');

        return DB::table('d_log')->insert($data);
    }
}

if(!function_exists('arraySequence')){

    /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
    function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
}
