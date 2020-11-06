<?php namespace App\models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Predis;
use App\models\Bmatchmap;

class Bevent extends Model {

	//
    protected $table = 'd_bevent';

    protected $guarded = ['id'];
    public $timestamps = false;

    public static $match_score_key = 'match_bscore';
    public static $live_score_key = 'live_bscore';
    public static $match_fn_key = 'match_bscore_fn';

    public function handle_blivescore($cli,&$up_match)
    {
        if ($cli->statusCode != 200 || !$cli->body){
            write_log('lu_http_error', $cli);
            return [];
        }

        $data = json_decode($cli->body, true);

        $matchmap = Bmatchmap::where('source','win007')->get(['match_id','out_matchid'])->toArray();

        $match_id_arr = array_column($matchmap,'match_id','out_matchid');

        $score = [];
        // var_dump($data['row']);
        if ( $data['row'] ) foreach ($data['row'] as $val) {
            $match_id = $match_id_arr[$val['bh']];

            if(!isset($match_id)){
                continue;
            }
            $score[$match_id]['match_id'] = $match_id;
            $score[$match_id]['match_status'] = $val['state'];
            $hq = $val['hs1']+$val['hs2']+$val['hs3']+$val['hs4']+$val['hsj'];
            $aq = $val['as1']+$val['as2']+$val['as3']+$val['as4']+$val['asj'];

            if($val['state'] == 9 || $val['state'] == 11 || $val['state'] == 12 || $val['state'] == 13 || $val['state'] == 14 || $val['state'] == 15 || $val['state'] == 16){

                $first_score = $val['hs1'].'-'.$val['as1'];
                $second_score = $val['hs2'].'-'.$val['as2'];
                $third_score = $val['hs3'].'-'.$val['as3'];
                $fourth_score = $val['hs4'].'-'.$val['as4'];
                $firstot = $val['hsj'].'-'. $val['asj'];

                $up_match[$match_id]['id'] = $match_id;
                $up_match[$match_id]['match_state'] = $val['state'];
                $up_match[$match_id]['score'] =$hq.'-'.$aq;
                $up_match[$match_id]['first_score'] = $first_score != '-' ? $first_score : '';

                $up_match[$match_id]['second_score'] = $second_score != '-' ? $second_score : '';
                $up_match[$match_id]['third_score'] = $third_score != '-' ? $third_score : '';
                $up_match[$match_id]['fourth_score'] = $fourth_score != '-' ? $fourth_score : '';

                $up_match[$match_id]['overtimes'] = '';

                $firstot != '-' && $up_match[$match_id]['firstot'] = $firstot;
            }

            $score[$match_id]['jieshu'] = $val['type'];
            $score[$match_id]['remain_time'] = $val['stime'];
            $score[$match_id]['home_points'] = $hq;
            $score[$match_id]['away_points'] = $aq;

            $score[$match_id]['home_first'] = $val['hs1'];
            $score[$match_id]['away_first'] = $val['as1'];
            $score[$match_id]['home_second'] = $val['hs2'];
            $score[$match_id]['away_second'] = $val['as2'];

            $score[$match_id]['home_third'] = $val['hs3'];
            $score[$match_id]['away_third'] = $val['as3'];
            $score[$match_id]['home_fourth'] = $val['hs4'];
            $score[$match_id]['away_fourth'] = $val['as4'];

            $score[$match_id]['live'] = '';
            $score[$match_id]['home_odds'] = '';
            $score[$match_id]['away_odds'] = '';
            $score[$match_id]['overtimes'] = '';

            $score[$match_id]['home_firstot'] = $val['hsj'];
            $score[$match_id]['away_firstot'] = $val['asj'];

            $score[$match_id]['home_secondot'] = '';
            $score[$match_id]['away_secondot'] = '';
            $score[$match_id]['home_thirdot'] = '';
            $score[$match_id]['away_thirdot'] = '';

        }

        return $score;

    }


    public static function convert_qtscore_history($data, &$up_match)
    {
        if(!isset($data->matchList) || empty($data->matchList)){
            return [];
        }
        $data = $data->matchList;
        $out_ids = array_column($data, 'matchId');

        $match_ids = Bmatch::whereIn('out_match_id',$out_ids)->get(['out_match_id','id']);
        $match_ids = $match_ids ? array_column($match_ids->toArray(), 'id','out_match_id') : [];

//        dd(explode('^', '赛事ID^联赛/杯赛ID^类型^联赛简体名,联赛繁体名^分几节进行^颜色值^开赛时间^状态^小节剩余时间^主队ID^主队简体名,主队繁体名^客队ID^客队简体名,客队繁体名^主队排名^客队排名^主队得分^客队得分^主队一节得分（上半场）^客队一节得分（上半场）^主队二节得分^客队二节得分^主队三节得分（下半场）^客队三节得分（下半场）^主队四节得分^客队四节得分^加时数^主队1\'ot得分^客队1\'ot得分^主队2\'ot得分^客队2\'ot得分^主队3\'ot得分^客队3\'ot得分^是否有技术统计^电视直播^备注^是否中立场'));
        $score = [];
        foreach ($data as $key=>$val){
            $match_id = isset($match_ids[$val->matchId]) ? $match_ids[$val->matchId] : 0;

            $match_state = self::get_match_state( $val->matchState, 'win007' );

            $end_status = [
                '第1节结束.' => 2,
                '第2节结束.' => 4,
                '第3节结束.' => 6,
                '第4节结束.' => 8,
            ];

            /*if ( in_array( $list[33], $end_status ) ) {
                $match_state = $end_status[$list[33]];
            }*/

            $score[$match_id]['match_id'] = $match_id;
            $score[$match_id]['match_status'] = $match_state;

            if($val->matchState == '-1'){
                $first_score = $val->home1.'-'.$val->away1;
                $second_score = $val->home2.'-'.$val->away2;
                $third_score = $val->home3.'-'.$val->away3;
                $fourth_score = $val->home4.'-'.$val->away4;

                $up_match[$match_id]['id'] = $match_id;
                $up_match[$match_id]['match_state'] = $match_state;
                $up_match[$match_id]['score'] = $val->homeScore.'-'.$val->awayScore;
                $up_match[$match_id]['first_score'] = $first_score != '-' ? $first_score : '';

                $up_match[$match_id]['second_score'] = $second_score != '-' ? $second_score : '';
                $up_match[$match_id]['third_score'] = $third_score != '-' ? $third_score : '';
                $up_match[$match_id]['fourth_score'] = $fourth_score != '-' ? $fourth_score : '';

                $up_match[$match_id]['overtimes'] = $val->overtimeCount;
                $val->overtimeCount >= 1 && $up_match[$match_id]['firstot'] = $val->homeOT1.'-'.$val->awayOT1;
                $val->overtimeCount >= 2 && $up_match[$match_id]['secondot'] = $val->homeOT2.'-'.$val->awayOT2;
                $val->overtimeCount >= 3 && $up_match[$match_id]['thirdot'] = $val->homeOT3.'-'.$val->awayOT3;
            }

            $score[$match_id]['jieshu'] = $val->leagueType;
            $score[$match_id]['remain_time'] = $val->remainTime;
            $score[$match_id]['home_points'] = $val->homeScore;
            $score[$match_id]['away_points'] = $val->awayScore;

            $score[$match_id]['home_first'] = $val->home1;
            $score[$match_id]['away_first'] = $val->away1;
            $score[$match_id]['home_second'] = $val->home2;
            $score[$match_id]['away_second'] = $val->away2;

            $score[$match_id]['home_third'] = $val->home3;
            $score[$match_id]['away_third'] = $val->away3;
            $score[$match_id]['home_fourth'] = $val->home4;
            $score[$match_id]['away_fourth'] = $val->away4;

            $score[$match_id]['live'] = $val->tv;
            $score[$match_id]['home_odds'] = '';
            $score[$match_id]['away_odds'] = '';

            $score[$match_id]['overtimes'] = $val->overtimeCount;

            $score[$match_id]['home_firstot'] = $val->homeOT1;
            $score[$match_id]['away_firstot'] = $val->awayOT1;

            $score[$match_id]['home_secondot'] = $val->homeOT2;
            $score[$match_id]['away_secondot'] = $val->awayOT2;
            $score[$match_id]['home_thirdot'] = $val->homeOT3;
            $score[$match_id]['away_thirdot'] = $val->awayOT2;
        }

        return $score;
    }

    public static function write_bscore($data)
    {

        return Predis::lpush(self::$live_score_key,json_encode($data));
    }

    public static function set_score_redis($data)
    {
        foreach ($data as $key=>$val){
            $ret[$key] =  Predis::hset(self::$match_score_key,$key, $val);
        }
//         $redis->expire($key,24*3600);
        return $ret;
    }

    public static function get_score_redis($match_ids = [])
    {
        if($match_ids){
            $data = Predis::hmget(self::$match_score_key,$match_ids);
            return array_combine($match_ids, $data);
        }

        return Predis::hgetall(self::$match_score_key);
    }

    public function handle_live_bscore($cli){

        $info = [];

        if($cli->statusCode != 200 || !$cli->body) {
            return [];
        }
        $res = (string)$cli->body;

        $data = json_decode($res,true);

        if(!$data['row']){
            return [];
        }

        $data = json_decode($cli->body, true);

        $out_matchids = array_column($data['row'],'bh');

        //$matchmap = Bmatchmap::where('source','win007')->get(['match_id','out_matchid'])->toArray();

        $matchmap = Bmatchmap::where('source','win007')->whereIn('out_matchid',$out_matchids)->get(['match_id','out_matchid'])->toArray();

        $match_id_arr = array_column($matchmap,'match_id','out_matchid');

        $score = $event_score = $match_score = $source_score = [];

        if ($data['row']) foreach ($data['row'] as $k=>$v) {

            $match_id = $match_id_arr[$v['bh']];

            if (!$match_id) {
                continue;
            }

            $hq = $v['hs1']+$v['hs2']+$v['hs3']+$v['hs4']+$v['hsj'];
            $aq = $v['as1']+$v['as2']+$v['as3']+$v['as4']+$v['asj'];

            if ($hq && $aq && $v['state']==0) {
                // var_dump($hq.'=>'.$aq.'=>'.$v['state'].'=>'.$v['bh']);
                continue;
            }

            $event_score[$match_id]['match_id'] = $match_id;
            $event_score[$match_id]['match_status'] = $v['state'];
            $event_score[$match_id]['jieshu'] = $v['type'];
            $event_score[$match_id]['remain_time'] = $v['stime'];
            $event_score[$match_id]['home_points'] = $hq;
            $event_score[$match_id]['away_points'] = $aq;
            $event_score[$match_id]['home_first'] = $v['hs1'];
            $event_score[$match_id]['away_first'] = $v['as1'];
            $event_score[$match_id]['home_second'] = $v['hs2'];
            $event_score[$match_id]['away_second'] = $v['as2'];
            $event_score[$match_id]['home_third'] = $v['hs3'];
            $event_score[$match_id]['away_third'] = $v['as3'];
            $event_score[$match_id]['home_fourth'] = $v['hs4'];
            $event_score[$match_id]['away_fourth'] = $v['as4'];
            $event_score[$match_id]['home_firstot'] = $v['hsj'];
            $event_score[$match_id]['away_firstot'] = $v['asj'];

            if($v['state'] == 9 || $v['state'] == 11 || $v['state'] == 12 || $v['state'] == 13 || $v['state'] == 14 || $v['state'] == 15 || $v['state'] == 16){

                $first_score = $v['hs1'].'-'.$v['as1'];
                $second_score = $v['hs2'].'-'.$v['as2'];
                $third_score = $v['hs3'].'-'.$v['as3'];
                $fourth_score = $v['hs4'].'-'.$v['as4'];
                $firstot = $v['hsj'].'-'. $v['asj'];

                $match_score[$match_id]['id'] = $match_id;
                $match_score[$match_id]['match_state'] = $v['state'];
                $match_score[$match_id]['score'] =$hq.'-'.$aq;
                $match_score[$match_id]['first_score'] = $first_score != '-' ? $first_score : '';

                $match_score[$match_id]['second_score'] = $second_score != '-' ? $second_score : '';
                $match_score[$match_id]['third_score'] = $third_score != '-' ? $third_score : '';
                $match_score[$match_id]['fourth_score'] = $fourth_score != '-' ? $fourth_score : '';

                $match_score[$match_id]['overtimes'] = '';

                $firstot != '-' && $match_score[$match_id]['firstot'] = $firstot;

                $source_score[$match_id]['score'] =$hq.'-'.$aq;
                $source_score[$match_id]['match_state'] = $v['state'];
                $source_score[$match_id]['home_first_score'] = $v['hs1'];
                $source_score[$match_id]['away_first_score'] = $v['as1'];
                $source_score[$match_id]['home_second_score'] = $v['hs2'];
                $source_score[$match_id]['away_second_score'] = $v['as2'];
                $source_score[$match_id]['home_third_score'] = $v['hs3'];
                $source_score[$match_id]['away_third_score'] = $v['as3'];
                $source_score[$match_id]['home_fourth_score'] = $v['hs4'];
                $source_score[$match_id]['away_fourth_score'] = $v['as4'];
                $source_score[$match_id]['home_ot'] = $v['hsj'];
                $source_score[$match_id]['away_ot'] = $v['asj'];

            }
        }

        $score['event_score'] = $event_score;
        $score['match_score'] = $match_score;
        $score['source_score'] = $source_score;

        return $score;
    }

    public function handle_blive_list($data,&$up_match)
    {

        if(!$data['row']){
            return [];
        }

        $log['bh'] = array_column($data['row'],'bh');
        $log['content'] = $data['row'];
        $log['match_id'] = $data['c']['fn'];
        write_log('lu_win007_bscore',json_encode($log));
        $out_matchids = array_column($data['row'],'bh');

        $matchmap = Bmatchmap::where('source','win007')->whereIn('out_matchid',$out_matchids)->get(['match_id','out_matchid']);
        if (!$matchmap) {
            return [];
        }
        $matchmap && $matchmap = $matchmap->toArray();

        $match_id_arr = array_column($matchmap,'match_id','out_matchid');

        $score = [];

        if ( $data['row'] ) foreach ($data['row'] as $val) {
            $match_id = $match_id_arr[$val['bh']];

            if(!isset($match_id)){
                continue;
            }
            $score[$match_id]['match_id'] = $match_id;
            $score[$match_id]['match_status'] = $val['state'];
            $hq = $val['hs1']+$val['hs2']+$val['hs3']+$val['hs4']+$val['hsj'];
            $aq = $val['as1']+$val['as2']+$val['as3']+$val['as4']+$val['asj'];

            if($val['state'] == 9 || $val['state'] == 11 || $val['state'] == 12 || $val['state'] == 13 || $val['state'] == 14 || $val['state'] == 15 || $val['state'] == 16){

                $first_score = $val['hs1'].'-'.$val['as1'];
                $second_score = $val['hs2'].'-'.$val['as2'];
                $third_score = $val['hs3'].'-'.$val['as3'];
                $fourth_score = $val['hs4'].'-'.$val['as4'];
                $firstot = $val['hsj'].'-'. $val['asj'];

                $up_match[$match_id]['id'] = $match_id;
                $up_match[$match_id]['match_state'] = $val['state'];
                $up_match[$match_id]['score'] =$hq.'-'.$aq;
                $up_match[$match_id]['first_score'] = $first_score != '-' ? $first_score : '';

                $up_match[$match_id]['second_score'] = $second_score != '-' ? $second_score : '';
                $up_match[$match_id]['third_score'] = $third_score != '-' ? $third_score : '';
                $up_match[$match_id]['fourth_score'] = $fourth_score != '-' ? $fourth_score : '';

                $up_match[$match_id]['overtimes'] = '';

                $firstot != '-' && $up_match[$match_id]['firstot'] = $firstot;
            }

            $score[$match_id]['jieshu'] = $val['type'];
            $score[$match_id]['remain_time'] = $val['stime'];
            $score[$match_id]['home_points'] = $hq;
            $score[$match_id]['away_points'] = $aq;

            $score[$match_id]['home_first'] = $val['hs1'];
            $score[$match_id]['away_first'] = $val['as1'];
            $score[$match_id]['home_second'] = $val['hs2'];
            $score[$match_id]['away_second'] = $val['as2'];

            $score[$match_id]['home_third'] = $val['hs3'];
            $score[$match_id]['away_third'] = $val['as3'];
            $score[$match_id]['home_fourth'] = $val['hs4'];
            $score[$match_id]['away_fourth'] = $val['as4'];

            $score[$match_id]['live'] = '';
            $score[$match_id]['home_odds'] = '';
            $score[$match_id]['away_odds'] = '';
            $score[$match_id]['overtimes'] = '';

            $score[$match_id]['home_firstot'] = $val['hsj'];
            $score[$match_id]['away_firstot'] = $val['asj'];

            $score[$match_id]['home_secondot'] = '';
            $score[$match_id]['away_secondot'] = '';
            $score[$match_id]['home_thirdot'] = '';
            $score[$match_id]['away_thirdot'] = '';

        }

        return $score;

    }

    public static function get_score()
    {
        return Predis::rpop(self::$live_score_key);
    }

    public function get_fn_redis($fn){
        if (!$fn) {
            return false;
        }
        $ret = Predis::get(self::$match_fn_key);

        return $ret;
    }

    public function set_fn_redis($fn)
    {
        $ret =  Predis::et(self::$match_fn_key, $fn);

        return $ret;
    }

    public static function convert_qtscore($data, &$up_match)
    {
        if(!isset($data->changeList) || empty($data->changeList)){
            return [];
        }
        $data = $data->changeList;
        $out_ids = array_column($data, 'matchId');
        $match_ids = Bmatch::whereIn('out_match_id',$out_ids)->get(['out_match_id','id']);
        $match_ids = $match_ids ? array_column($match_ids->toArray(), 'id','out_match_id') : [];

        $score = [];
        foreach ($data as $key => $val){
            $match_id = isset($match_ids[$val->matchId]) ? $match_ids[$val->matchId] : 0;

            $match_state = self::get_match_state( $val->matchState, 'win007' );

            $end_status = [
                '第1节结束.' => 2,
                '第2节结束.' => 4,
                '第3节结束.' => 6,
                '第4节结束.' => 8,
            ];

            /*if ( in_array( $list[33], $end_status ) ) {
                $match_state = $end_status[$list[33]];
            }*/

            $score[$match_id]['match_id'] = $match_id;
            $score[$match_id]['match_status'] = $match_state;

            if($val->matchState == '-1'){
                $first_score = $val->home1.'-'.$val->away1;
                $second_score = $val->home2.'-'.$val->away2;
                $third_score = $val->home3.'-'.$val->away3;
                $fourth_score = $val->home4.'-'.$val->away4;

                $up_match[$match_id]['id'] = $match_id;
                $up_match[$match_id]['match_state'] = $match_state;
                $up_match[$match_id]['score'] = $val->homeScore.'-'.$val->awayScore;
                $up_match[$match_id]['first_score'] = $first_score != '-' ? $first_score : '';

                $up_match[$match_id]['second_score'] = $second_score != '-' ? $second_score : '';
                $up_match[$match_id]['third_score'] = $third_score != '-' ? $third_score : '';
                $up_match[$match_id]['fourth_score'] = $fourth_score != '-' ? $fourth_score : '';

                $up_match[$match_id]['overtimes'] = $val->overtimeCount;
                $val->overtimeCount >= 1 && $up_match[$match_id]['firstot'] = $val->homeOT1.'-'.$val->awayOT1;
                $val->overtimeCount >= 2 && $up_match[$match_id]['secondot'] = $val->homeOT2.'-'.$val->awayOT2;
                $val->overtimeCount >= 3 && $up_match[$match_id]['thirdot'] = $val->homeOT3.'-'.$val->awayOT3;
            }

//            $score[$match_id]['jieshu'] = $val->leagueType;
            $score[$match_id]['remain_time'] = $val->remainTime;
            $score[$match_id]['home_points'] = $val->homeScore;
            $score[$match_id]['away_points'] = $val->awayScore;

            $score[$match_id]['home_first'] = $val->home1;
            $score[$match_id]['away_first'] = $val->away1;
            $score[$match_id]['home_second'] = $val->home2;
            $score[$match_id]['away_second'] = $val->away2;

            $score[$match_id]['home_third'] = $val->home3;
            $score[$match_id]['away_third'] = $val->away3;
            $score[$match_id]['home_fourth'] = $val->home4;
            $score[$match_id]['away_fourth'] = $val->away4;

//            $score[$match_id]['live'] = $val->tv;
            $score[$match_id]['home_odds'] = '';
            $score[$match_id]['away_odds'] = '';

            $score[$match_id]['overtimes'] = $val->overtimeCount;

            $score[$match_id]['home_firstot'] = $val->homeOT1;
            $score[$match_id]['away_firstot'] = $val->awayOT1;

            $score[$match_id]['home_secondot'] = $val->homeOT2;
            $score[$match_id]['away_secondot'] = $val->awayOT2;
            $score[$match_id]['home_thirdot'] = $val->homeOT3;
            $score[$match_id]['away_thirdot'] = $val->awayOT2;
//            $score[$match_id]['out_matchid'] = $list[0];
        }

        return $score;
    }

    public static function get_match_state( $state, $source ) {

        /*$win007_state_map = [
            0 => 0, //未开赛
            1 => 1, //第一节
            2 => 3, //第二节
            3 => 5, //第三节
            4 => 7, //第四节
            5 => 10, //第一节加时
            6 => 10, //第二节加时
            7 => 10, //第三节加时
            8 => 10, //第四节加时
            50 => 4, //中场---第二节结束
            '-1' => 9,  //完场
            '-2' => 16,  //待定
            '-3' => 12,  //中断
            '-4' => 13,  //取消
            '-5' => 14,  //延期

        ];*/
        $win007_state_map = [
            0 => 0, //未开赛
            1 => 1, //第一节
            2 => 2, //第二节
            3 => 3, //第三节
            4 => 4, //第四节
            5 => 5, //第一节加时
            6 => 6, //第二节加时
            7 => 7, //第三节加时
            8 => 8, //第四节加时
            50 => 50, //中场---第二节结束
            '-1' => '-1',  //完场
            '-2' => '-2',  //待定
            '-3' => '-3',  //中断
            '-4' => '-4',  //取消
            '-5' => '-5',  //延期

        ];

        $status = $state;

        if ( $source == 'win007' ) {

            $status = $state;

            if ( isset( $win007_state_map[$state] ) ) {

                $status = $win007_state_map[$state];

            }

        }

        return $status;

    }

    /**
     *
     * @param unknown $val
     * @return multitype:
     */
    private function _get_ids($val)
    {
        return explode('^', $val['value'])[0];
    }




}

























