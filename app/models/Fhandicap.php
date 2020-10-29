<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fhandicap extends Model
{
    protected $table = 'd_handicapodds';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static $handicap_arr = [
        '4.00' => '受四球', '3.75' => '受三半/四', '3.50' => '受三球半', '3.25' => '受三/三半', '3.00' => '受三球', '2.75' => '受两半/三', '2.50' => '受两球半', '2.25' => '受两/两半',
        '2.00' => '受两球', '1.75' => '受球半/两', '1.50' => '受一球半', '1.25' => '受一/球半', '1.00' => '受一球', '0.75' => '受半/一',  '0.50' => '受半球', '0.25' => '受平/半',
        '0.00' => '平手', '-0.25' => '平/半', '-0.50' => '半球', '-0.75' => '半/一', '-1.00' => '一球', '-1.25' => '一/球半', '-1.50' => '一球半',
        '-1.75' => '球半/两', '-2.00' => '两球', '-2.25' => '两/两半', '-2.50' => '两球半', '-2.75' => '两半/三', '-3.00' => '三球', '-3.25' => '三/三半', '-3.50' => '三球半', '-3.75' => '三半/四',
        '-4.00' => '四球','-1.75' => '球半/两球',

    ];

    public static $handicap_map = [
        '受四球' => '4.00',
        '受三球半/四球' => '3.75',
        '受三球半' => '3.50',
        '受三球/三球半' => '3.25',
        '受三球' => '3.00',
        '受两球半/三球' => '2.75',
        '受两球半' => '2.50',
        '受两球/两球半' => '2.25',
        '受两球' => '2.00',
        '受一球半/两球' => '1.75',
        '受一球半' => '1.50',
        '受一球/一球半' => '1.25',
        '受一球' => '1.00',
        '受一球半/一球' => '0.75',
        '受半球' => '0.50',
        '受平手/半球' => '0.25',
        '平手' => '0.00',
        '平手/半球' => '-0.25',
        '半球' => '-0.50',
        '半球/一球' => '-0.75',
        '一球' => '-1.00',
        '一球/一球半' => '-1.25',
        '一球半' => '-1.50',
        '球半' => '-1.50',
        '一球/球半' => '-1.25',
        '一球半/两球' => '-1.75',
        '两球' => '-2.00',
        '两球/两球半' => '-2.25',
        '两球半' => '-2.50',
        '两球半/三球' => '-2.75',
        '三球' => '-3.00',
        '三球/三球半' => '-3.25',
        '三球半' => '-3.50',
        '三球半/四球' => '-3.75',
        '四球' => '-4.00',
        //win007
        '受半球/一球' => '0.75',
        '球半/两球' =>'-1.75',


        #win007
        '平/半' => '-0.25',
        '半/一' => '-0.75',

        '一/半' => '-1.25',
        '球半/两' => '-1.75',
        '两/半' => '-2.25',
        '两半"' => '-2.50',
        '两半/三' => '-2.75',
        '三/半' => '-3.25',
        '三半' => '-3.50',
        '三半/四' => '-3.75',
        '四/半' => '-4.25',
        '四半' => '-4.50',
        '四半/五' => '-4.75',
        '五' => '5.00',
        '五/五半' => '-5.25',
        '五半' => '-5.50',
        '五半/六' => '-5.75',

        '六' => '6.00',
        '六/六半' => '-6.25',
        '六半' => '-6.50',
        '六半/七' => '-6.75',

        '七球' => '7.00',
        '七/七半' => '-7.25',
        '七半' => '-7.50',
        '七半/八' => '-7.75',

        '八球' => '8.00',
        '八/八半' => '-8.25',
        '八半' => '-8.50',
        '八半/九' => '-8.75',

        '九球' => '9.00',
        '九/九半' => '-9.25',
        '九半' => '-9.50',
        '九半/十' => '-9.75',

        '十球' => '10.00',
        '十/十半' => '-10.25',
        '十半' => '-10.50',
        '十半/十一' => '-10.75',

        '十一球' => '11.00',
        '十一/十一半' => '-11.25',
        '十一半' => '-11.50',
        '十一半/十二球' => '-11.75',

        '十二球' => '12.00',
        '十二/十二半' => '-12.25',
        '十二半' => '-12.50',
        '十二半/十三球' => '-12.75',

        '十三球' => '13.00',
        '十三/十三半' => '-13.25',
        '十三半' => '-13.50',
        '十三半/十四球' => '-13.75',

        '十四球' => '14.00',
        '十四/十四半' => '-14.25',
        '十四半' => '-14.50',
        '十四半/十五球' => '-14.75',

        '十五球' => '15.00',
        '十五/十五半' => '-15.25',
        '十五半' => '-15.50',
        '十五半/十六球' => '-15.75',

        '十六球' => '16.00',
        '十六/十六半' => '-16.25',
        '十六半' => '-16.50',
        '十六半/十七球' => '-16.75',

        '十七球' => '17.00',
        '十七/十七半' => '-17.25',
        '十七半' => '-17.50',
        '十七半/十八球' => '-17.75',

        '十八球' => '18.00',
        '十八/十八半' => '-18.25',
        '十八半' => '-18.50',
        '十八半/十九球' => '-18.75',

        '十九球' => '19.00',
        '十九/十九半' => '-19.25',
        '十九半' => '-19.50',
        '十九半/二十球' => '-19.75',


        '受球半' => '1.50',
        '受一球/球半' => '1.25',

    ];

    public static function handle_QtOdds($out_data)
    {
        $source = 'win007';
        $out_matchids = [];
        foreach ($out_data->match as $key => $val) {
            $out_matchids[] = $val->matchId;
        }
        $matchData = Fmatch::whereIn('out_match_id', $out_matchids)->get(['match_id', 'out_match_id'])->toArray();
        if (!$matchData || count($matchData) == 0) {
            return false;
            exit;
        }
        $match_ids = [];
        foreach ($matchData as $key => $val) {
            $match_ids[$val['out_match_id']] = $val['match_id'];
        }
        $book_data = bookmaker::where(['type' => 2])->get(['bookmaker_id','out_bookmaker_id'])->toArray();
        $bookid_maps = array_column($book_data, 'bookmaker_id','out_bookmaker_id');

        $Match_odds = $out_data->handicap;

        $now_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s',time()-5);
        $odds = [];
        foreach ($Match_odds as $mkey => $mval)
        {
            $out_matchid = $mval[0];
            $out_bookMaker_id = $mval[1];
            $init_handicap_num = $mval[2];
            $init_home = $mval[3];
            $init_away = $mval[4];
            $handicap_num = $mval[5];
            $home = $mval[6];
            $away = $mval[7];
            $zoudi = $mval[9];
            if($zoudi){
                continue;
            }
//            dd($out_matchid,$out_bookMaker_id,$init_handicap_num,$init_home,$init_away,$handicap_num,$home,$away);

            $match_id = isset($match_ids[$out_matchid]) ? $match_ids[$out_matchid] : 0;
            /*if(!$match_id){
                continue ;
            }*/
            $bookMakerId = isset($bookid_maps[$out_bookMaker_id]) ? $bookid_maps[$out_bookMaker_id] : false;
            if(!$bookMakerId){
                continue ;
            }
            $init_handicap_num = $init_handicap_num * -1;
            $handicap_num = $handicap_num * -1;
            $odds[] = [
                'start' => [
                    'match_id' => $match_id,
                    'out_match_id' => $out_matchid,
                    'bookmaker_id' => $bookMakerId,
                    'odds_type' => 0,
                    'handicap_num' => $init_handicap_num,
                    'home' => $init_home,
                    'away' => $init_away,
                    'add_time' => $start_time,
                    'update_time' => $start_time,
                ],
                'end' => [
                    'match_id' => $match_id,
                    'out_match_id' => $out_matchid,
                    'bookmaker_id' => $bookMakerId,
                    'odds_type' => 1,
                    'handicap_num' => $handicap_num,
                    'home' => $home,
                    'away' => $away,
                    'add_time' => $now_time,
                    'update_time' => $now_time,
                ],
            ];
        }

        return $odds;
    }

    public static function handle_QtLiveodds($out_data, $bookid_maps)
    {
        static $source = 'win007';
        if(empty($out_data) || !is_array($out_data)){
            return false;
        }

        $Match_odds = $out_data;

        $now_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s',time()-5);

        $odds = [];

        foreach ($Match_odds as $mkey=>$mval)
        {
            $out_matchid = $mval[0];
            $out_bookMaker_id = $mval[1];
            $handicap_num = $mval[2];
            $home = $mval[3];
            $away = $mval[4];
            $zoudi = $mval[6];
            if($zoudi){
                continue;
            }

            $matchData = Fmatch::where('out_match_id', $out_matchid)->first();
            $match_id = $matchData ? (int) $matchData->toArray()['match_id'] : 0;

            $handicap_num = $handicap_num * -1;


            $bookMakerId = (int) $bookid_maps[$out_bookMaker_id];
            ###
            /*if(!$bookMakerId){
                continue;
            }*/
            //对于盘口增加数值校验，大于1.5的可能是包含了本金的，要减去之后在入库
            if($home > 1.5 && $away > 1.5) {
                $home = $home - 1;
                $away = $away - 1;
            }
            //盘口校验数值结束

            $odds[] = [
                'match_id' => (int) $match_id,
                'bookmaker_id' => (int) $bookMakerId,
                'odds_type' => 0,
                'handicap_num' => (float) $handicap_num,
                'home' => $home,
                'away' => $away,
                'add_time' => $start_time,
                'update_time' => $start_time,
            ];
        }
        return $odds;
    }

    public static function compareInsert($odds, $eodds = [])
    {

        $eodds = $eodds ? $eodds : $odds;
        $startOdds = self::where(['match_id' => $odds['match_id'],'out_match_id' => $odds['out_match_id'],'bookmaker_id' => $odds['bookmaker_id'], 'handicap_num' => $odds['handicap_num']])->first();
        if(!$startOdds && is_array($odds)){
            Fhandicap::create($odds);
        }
        $endOdds = self::where(['match_id' => $odds['match_id'],'out_match_id' => $odds['out_match_id'],'bookmaker_id' => $odds['bookmaker_id']])->orderBy('updated_at','desc')->first()->toArray();
        print_r($endOdds);
        if($endOdds['update_time'] >= $eodds['update_time']){
            return false;
        }

        if(!$endOdds || $endOdds['home'] != $eodds['home'] || $endOdds['away'] != $eodds['away'] || $endOdds['handicap_num'] != $eodds['handicap_num']){
            $eodds['odds_type'] = 1;
            if ( !$eodds['update_time'] ) {
                $eodds['update_time'] = date('Y-m-d H:i:s');
            }
            Fhandicap::create($eodds);
            return true;
        }
        return false;
    }

}