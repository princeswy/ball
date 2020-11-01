<?php namespace App\lib;

//use App\lib\Uxmlwriter;
use App\lib\xmlToArray;

class Xml {
    private $apiurl = "http://dataweb.api.zs310.com/services/index";
    private $key = "4r5t6y3e2w"; //使用时换成自己的密钥

    public $reqxml;
    public $crc;


    public function __construct(){
        $this->objXmlToArray = new xmlToArray();
    }


    public function reqxml($input=array()){
        $uxmlwriter = new Uxmlwriter();
        if(isset($input['attr'])){
            $uxmlwriter->addrootElementAttr($input['attr']);
        }
        if(isset($input['nodes'])){
            $uxmlwriter->fromArray($input['nodes']);
        }
        $xml = $uxmlwriter->getDocument();
        $reqxml = $this->encode($xml);
        $crc = $this->md5crc($reqxml);
        $this->crc = $crc;
        $this->reqxml = $reqxml;
        return $this;
    }
    /**
     *
     * md5 crc 校验
     * @param $msg
     */
    public function md5crc($msg){
        return md5($msg.$this->key);
    }

    /**
     *
     * 加密算法
     * @param string $xml
     */
    public function encode($xml=''){
        return base64_encode(gzcompress($xml));
    }

    /**
     *
     * 消息解密
     * @param string $str
     */
    public function decode($str){
        return gzuncompress(base64_decode($str));
    }


    public function g_data(){
        $data          = curl_file_contents($this->apiurl,180,true,array('msg'=>$this->reqxml,'crc'=>$this->crc));
        if(!$data){
            return false;
        }
        $this->objXmlToArray->parseString($data);
        $xarr = $this->objXmlToArray->getTree();
        // 	    print_r($xarr);
        $this->objXmlToArray->free();
        if($xarr){
            $MSG = $xarr['MSG'];
            $crc = $MSG['crc'];
            $xmlstr = $MSG['value'];
            //crc 校验
            $mycrc = $this->md5crc($xmlstr);
            if ($mycrc == $crc){
                $objxml = $this->decode($xmlstr);
                return $this->xml_toarr($objxml);
                // 	            return $objxml;

            }else{
                return "crc 校验出错";
            }
        }
        return "数据异常！";
    }


    public static  function _gdata($url,$time = 60,$ispost = false, &$content = false){
        $data = curl_file_contents($url,$time,$ispost);

        if($content !== false){
            $content = $data;
        }

        if(!$data && $ispost == false)
        {
            unset($output);
            $cmd = "curl -m $time $url 2>/dev/null";
            @exec($cmd,$output);
            if(substr($output[0], 0, 5) != '<?xml'){
                return false;
            }
            $data = $output[0];
        }
        if(!$data){
            return false;
        }
        $objXmlToArray = new xmlToArray();
        $objXmlToArray->parseString($data);
        $xarr = $objXmlToArray->getTree();
        return $xarr;
    }


    public static function xml_toarr($objxml){
        $objXmlToArray = new xmlToArray();
        $objXmlToArray->parseString($objxml);
        $arr_data = $objXmlToArray->getTree();
        return $arr_data;
    }


    function xml_encode($data, $sid ,$type, $root='Req', $item='query', $attr='', $id='id', $encoding='utf-8') {
//         if(is_array($attr)){
//             $_attr = array();
//             foreach ($attr as $key => $value) {
//                 $_attr[] = "{$key}=\"{$value}\"";
//             }
//             $attr = implode(' ', $_attr);
//         }
//         $attr   = trim($attr);
//         $attr   = empty($attr) ? '' : " {$attr}";
        $xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml   .= "<{$root}{$attr} sid='{$sid}' memo=''>";
        $xml   .= "<{$item} type='{$type}'>";
//         $xml   .= $this->data_to_xml($data, $item, $id);
        $xml   .= "</{$root}>";
        echo $xml;
        return $xml;
    }



    function xml_decode($xmldata){
//         $xmldata = iconv('gbk','utf-8',$xmldata);
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$xmldata,true)){
            xml_parse_into_struct($xml_parser,$xmldata,$values);
            xml_parser_free($xml_parser);
            return $values; //非法格式
        }else {
            return (json_decode(json_encode(simplexml_load_string($xmldata)),true));
        }

        //$postObj = simplexml_load_string($xmldata,'SimpleXMLElement',LIBXML_NOCDATA); //

    }

    function data_to_xml($data, $item='item', $id='id') {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if(is_numeric($key)){
                $id && $attr = " {$id}=\"{$key}\"";
                $key  = $item;
            }
            $xml    .=  "<{$key}{$attr}>";
            $xml    .=  (is_array($val) || is_object($val)) ? $this->data_to_xml($val, $item, $id) : $val;
            $xml    .=  "</{$key}>";
        }
        return $xml;
    }
}

/* End of file xml.php */