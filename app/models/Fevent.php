<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use App\lib\Xml;
use Illuminate\Database\Eloquent\Model;
use Predis;

class Fevent extends Model
{
    protected $table = 'd_matchevent';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static $match_score_key = 'match_fscore';
    public static $score_source_key = 'score_source';

    public static $live_score_key = 'fmatch_score';
    public static $score_change_key = 'score_change';

    public static $matchsource_score_key = 'match_score_';

    public static $status_name = [
        1 => '上半场', 2 => '中场',3 => '下半场',
        4 => '完场',5 => '中断', 6 => '取消',
        7 => '加时', 8 => '加时',9 => '加时',
        10 => '完场',11 => '点球', 12 => '全',
        13 => '延期',14 => '腰斩',15 => '待定',
        16 => '金球',17 => '未开赛',

        32=> '等待加时赛',33=> '加时赛中场',34 => '等待点球决胜',
        61=> '推迟开赛',
        90 => '弃赛',
        110 => '加时赛后',
        120 => '点球决胜后',
    ];

    static $_status = [
        '0' => 17, '1' => 1, '2' => 2, '3' => 3, '4' => 7, '-11' => 15, '-12' =>14, '-13' => 5, '-14' => 13, '-1' => 4, '-10' => 6,
    ];


    public static function convert_qtscore_new($odata)
    {
        static $source = 'win007';

        if ( !$odata ) {
            return false;
        }

        $odata = Xml::xml_toarr($odata);

        if (!$odata || !isset($odata['c']['h'] ) || !is_array($odata['c']['h'])) {
            return [];
        }
        $Score = self::_foreach_data($odata['c']['h']);

        $datas = [];
        foreach ($Score as $key=>$val){

            $s_data = explode('^', $val['value']);

            $time = explode(',',$s_data[9]);
            $time = mktime($time[3],$time[4],$time[5],$time[1]+1,$time[2],$time[0]);

            $out_matchid = $s_data[0];

            //match_source data
            $source = [
                'sc' => ($s_data[2] ? $s_data[2] : 0).'-'.($s_data[3] ? $s_data[3] : 0),
                'bc' => ($s_data[4] ? $s_data[4] : 0).'-'.($s_data[5] ? $s_data[5] : 0),
                'hr' => ($s_data[6] ? $s_data[6]: 0).'-'.($s_data[7] ? $s_data[7] : 0),
                'hy' => ($s_data[12] ? $s_data[12] : 0).'-'.($s_data[13] ? $s_data[13] : 0),
                'co' => ($s_data[16] ? $s_data[16] : 0).'-'.($s_data[17] ? $s_data[17] : 0),
                'ss' => $s_data[1],
                'start_time' => date('Y-m-d H:i:s', $time),
                'out_matchid' => $out_matchid,

            ];

            $sources[$out_matchid] = $source;

            unset( $source);

        }

        return $sources;
    }

    public static function updateData($data) {
        $status = self::geteventstatus($data['ss']);
        $eventData = [
            'match_id' => $data['match_id'],
            'out_matchid' => $data['out_matchid'],
            'start_time' => $data['start_time'],
            'status' => $status
        ];
        list($eventData['home_half_goal'], $eventData['guest_half_goal']) = explode('-', $data['bc'], 2);
        list($eventData['home_goal'], $eventData['guest_goal']) = explode('-', $data['sc'], 2);
        list($eventData['home_yc'], $eventData['guest_yc']) = explode('-', $data['hy'], 2);
        list($eventData['home_rc'], $eventData['guest_rc']) = explode('-', $data['hr'], 2);
        if (Fevent::where($eventData)->first()) {
            return false;
        }
        Fevent::updateOrCreate(['out_matchid' => $data['out_matchid']], $eventData);

        $matchData = [
            'match_id' => $data['match_id'],
            'out_match_id' => $data['out_matchid'],
            'match_state' => $data['ss'],
            'half_score' => $data['bc'],
            'score' => $data['sc'],
        ];
        list($matchData['home_red'], $matchData['guest_red']) = explode('-', $data['hr'], 2);
        list($matchData['home_yellow'], $matchData['guest_yellow']) = explode('-', $data['hy'], 2);
        list($matchData['home_corner'], $matchData['guest_corner']) = explode('-', $data['co'], 2);
        Fmatch::updateOrCreate(['out_match_id' => $data['out_matchid']], $matchData);

        $pushData = $data;
        $pushData['tstime'] = strtotime($data['start_time']);
        if ($pushData['tstime'] > time()) {
            $pushData['tstime'] = time();
        }

        if ($status != 0) {
            Fevent::write_score($pushData);
        }
    }

    public static function _foreach_data($toeach_data){

        if(!isset($toeach_data[0]) || count($toeach_data) == count($toeach_data, 1)){
            $data[] = $toeach_data;
        }else{
            $data = $toeach_data;
        }

        return $data;
    }

    public static function geteventstatus($source_status) {
        $statusmap = ['0' => 17, '1' => 1, '2' => 2, '3' => 3, '4' => 7, '-11' => 15, '-12' =>14, '-13' => 5, '-14' => 13, '-1' => 4, '-10' => 6,];
//        $status = $statusmap[$source_status];

        $status = isset($statusmap[$source_status]) ? $statusmap[$source_status] : $source_status;

        return $status;
    }

    public static function write_score($data){
//        Predis::set('aa', json_encode($data));
//        dd(json_encode($data));
//        $redis = Predis::connection('default');
//        $redis->set('a', 1);
        Predis::lpush(self::$live_score_key, json_encode($data));
//        Predis::lpush(self::$live_score_key, json_encode($data));
    }

    public static function get_score() {
        return Predis::rpop(self::$live_score_key);
    }
}