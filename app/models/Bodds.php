<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;
use Predis;

class Bodds extends Model
{
    //
    protected $table = 'd_bnwdodds';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static $odds_basic_key = 'BMATCH:BASIC:OODS:KEY:%s';
    public static $odds_change_key = 'BMATCH:CHANGE:OODS:KEY:%s';

    static $function = [
        '3W' => ['App\models\Bodds','update_oddscache'],
        'rf' => ['App\models\Bhcodds','update_oddscache'],
        'dxf' => ['App\models\Btotalodds', 'update_oddscache'],
    ];

    public static $bookmaker_map = [
        1 => '澳门',
        2 => '易胜博',
        3 => '皇冠',
        8 => 'Bet365',
        9 => '韦德',
        31 => '利记',
    ];

    public static function convert_qtOdds_old($out_data)
    {
        echo "<pre>";
        $source = 'win007';

        if(!isset($out_data->list) || empty($out_data->list) || !is_array($out_data->list)){
            return false;
        }
        $out_data = $out_data->list;

        $out_matchids = array_column($out_data, 'matchId');

        $match_ids = Bmatch::whereIn('out_match_id', $out_matchids)->get(['id', 'out_match_id']);

        if ( $match_ids ) {
            $match_ids = $match_ids->toArray();
            $match_ids = array_column($match_ids, 'id', 'out_match_id');
        } else {
            return false;
        }

        $intersect_key = array_intersect($out_matchids, array_flip(array_filter($match_ids)));

        $intersect_data = array_intersect_key($out_data, $intersect_key);

        if(empty($intersect_data)){
            return false;
        }
        $Match_odds = $intersect_data ? $intersect_data: $out_data;

        $book_data = bookmaker::where(['type' => 3])->get(['bookmaker_id','out_bookmaker_id'])->toArray();
        $bookid_maps = array_column($book_data, 'bookmaker_id','out_bookmaker_id');

        $now_time = date('Y-m-d H:i:s');
        $odds = [];

        foreach ($Match_odds as $mkey=>$mval)
        {
            $match_id = (int) isset($match_ids[$mval->matchId]) ? $match_ids[$mval->matchId] : 0;

            /*if(!$match_id){
                continue ;
            }*/
            $Odds = $mval->oddsList;
            ##比赛外部博彩id
//            dd(array_column($Odds, 'odds'));
//            $out_bookMaker_Ids = array_map(['self','_get_bookmakerId'], array_column($Odds, 'odds'));
//            $out_bookMaker_names = array_map(['self','_get_bookmakerName'], array_column($Odds, 'odds'));
            foreach ($Odds as $okey => $oval)
            {
                if(!$oval){
                    continue;
                }

                list($out_bookMaker_id,$out_bookMaker_name,$s_win,$s_lost,$win,$lost) = $Odata = explode(',', $oval->odds);
                $up_time = $oval->changeTime;

                $bookMakerId = (int) isset($bookid_maps[$out_bookMaker_id]) ? $bookid_maps[$out_bookMaker_id] : 0;
                if ( $bookMakerId == 0 ) {
                    $bookMakerId = self::save_bookemaker($out_bookMaker_id, $out_bookMaker_name, 3);
                }

                $up_time =  date('Y-m-d H:i:s',strtotime($up_time));
                if(!$bookMakerId){
                    ##
                    continue;
                }
                $odds[] = [
                    'start' =>[
                        'match_id' => (int) $match_id,
                        'bookmaker_id' => (int) $bookMakerId,
                        'out_bookmaker_id' => (int) $out_bookMaker_id,
                        'bookmaker_name' => $out_bookMaker_name,
                        'odds_type' => 0,
                        'win' => $s_win,
                        'lost' => $s_lost,
                        'add_time' => $now_time,
                        'update_time' => $win ? date('Y-m-d H:i:s',strtotime($up_time) - 60) : $up_time,
                    ],
                    'end' => [
                        'match_id' => (int) $match_id,
                        'bookmaker_id' => (int) $bookMakerId,
                        'out_bookmaker_id' => (int) $out_bookMaker_id,
                        'bookmaker_name' => $out_bookMaker_name,
                        'odds_type' => 1,
                        'win' => $win,
                        'lost' => $lost,
                        'add_time' => $now_time,
                        'update_time' => $up_time,
                    ],
                ];

            }


        }

        return $odds;
    }

    public static function convert_qtOdds($out_data)
    {
        echo "<pre>";
        $source = 'win007';
        if(!isset($out_data) || empty($out_data)){
            return false;
        }

        $now_time = date('Y-m-d H:i:s');

        foreach ( $out_data as $key => $val ) {
//            list($match_id, $out_bookMaker_id, $shand, $swin, $slost, $hand, $win, $lost) = explode(',', $val);
            $match_id = $val[0];
            $out_bookMaker_id = $val[1];
            $swin = $val[2];
            $slost = $val[3];
            $win = $val[4];
            $lost = $val[5];
//            list($match_id, $out_bookMaker_id, $swin, $slost, $win, $lost) = explode(',', $val);
            $match_data[$match_id]['out_match_id'] = $match_id;
            $match_data[$match_id]['out_bookmaker_id'] = $out_bookMaker_id;
            $match_data[$match_id]['swin'] = $swin;
            $match_data[$match_id]['slost'] = $slost;
            $match_data[$match_id]['win'] = $win;
            $match_data[$match_id]['lost'] = $lost;

        }

        $out_matchids = array_column($match_data, 'out_match_id');
        $out_bookids = array_column($match_data, 'out_bookmaker_id');

        $match_ids = Bmatch::whereIn('out_match_id', $out_matchids)->get(['id', 'out_match_id']);

        if ( $match_ids ) {
            $match_ids = $match_ids->toArray();
            $match_ids = array_column($match_ids, 'id', 'out_match_id');
        } else {
            return false;
        }

        $intersect_key = array_intersect($out_matchids, array_flip(array_filter($match_ids)));
        $intersect_data = array_intersect_key($match_data, array_flip($intersect_key));

        if(empty($intersect_data)){
            return false;
        }

        $Match_odds = $intersect_data ? $intersect_data: $out_data;

        $book_data = bookmaker::where(['type' => 3])->get(['bookmaker_id','out_bookmaker_id'])->toArray();
        $bookid_maps = array_column($book_data, 'bookmaker_id','out_bookmaker_id');

        $odds = [];

        foreach ($Match_odds as $mkey => $mval)
        {

            $match_id = (int) $match_ids[$mval['out_match_id']];
            $s_win = $mval['swin'];
            $s_lost = $mval['slost'];
            $win = $mval['win'];
            $lost = $mval['lost'];
            $out_bookMaker_id = $mval['out_bookmaker_id'];

            if(!$match_id){
                continue ;
            }
            ##比赛外部博彩id
            $out_bookMaker_Ids[] = $mval['out_bookmaker_id'];

            $bookMakerId = (int) isset($bookid_maps[$out_bookMaker_id]) ? $bookid_maps[$out_bookMaker_id] : 0;

            $bookmaker_name = isset(self::$bookmaker_map[$out_bookMaker_id]) ? self::$bookmaker_map[$out_bookMaker_id] : '';
            if ( $bookMakerId == 0 ) {
                $bookMakerId = self::save_bookemaker($out_bookMaker_id, $bookmaker_name, $source, 4);
            }

            $odds[] = [
                'start' =>[
                    'match_id' => (int) $match_id,
                    'bookmaker_id' => (int) $bookMakerId,
                    'out_match_id' => (int) $mval['out_match_id'],
                    'out_bookmaker_id' => (int) $mval['out_bookmaker_id'],
                    'bookmaker_name' => $bookmaker_name,
                    'odds_type' => 0,
                    'win' => $s_win,
                    'lost' => $s_lost,
                    'add_time' => $now_time,
                    'update_time' => $win ? date('Y-m-d H:i:s',strtotime($now_time) - 60) : $now_time,
                ],
                'end' => [
                    'match_id' => (int) $match_id,
                    'bookmaker_id' => (int) $bookMakerId,
                    'out_match_id' => (int) $mval['out_match_id'],
                    'out_bookmaker_id' => (int) $mval['out_bookmaker_id'],
                    'bookmaker_name' => $bookmaker_name,
                    'odds_type' => 1,
                    'win' => $win,
                    'lost' => $lost,
                    'add_time' => $now_time,
                    'update_time' => date('Y-m-d H:i:s',strtotime($now_time) - 60),
                ],
            ];

        }

        return $odds;
    }

    /**
     *
     * @param string $str
     * @return string
     */
    public static function _get_bookmakerId($str){
        return substr($str, 0 ,strpos($str, ','));
    }

    public static function _get_bookmakerName($str){
        $map = explode(',', $str);
        return $map[1];
//        return substr($str, 2 ,strpos($str, ',', 0));
    }

    /**
     *
     * @param int $out_bookmakerid string $out_bookmakername string $source int $type
     * @return int
     */
    public static function save_bookemaker($out_bookmakerid, $out_bookmakername, $type) {
        $bookmaker_id = bookmaker::firstOrCreate(['bookmaner_name' => $out_bookmakername, 'out_bookmaker_id' => $out_bookmakerid, 'type' => $type])->bookmaker_id;
        return $bookmaker_id;
    }

    /**
     *
     * @param array $odds
     * @return boolean
     */
    public static function compareInsert($odds,$eodds = [])
    {
        $eodds = $eodds ? $eodds : $odds;
        $startOdds = self::where(['match_id' => $odds['match_id'],'bookmaker_id' => $odds['bookmaker_id'],'odds_type' => 0,])->first();

        if(!$startOdds){
            self::create($odds);
        }
        $endOdds = self::where(['match_id' => $odds['match_id'],'bookmaker_id' => $odds['bookmaker_id'],'odds_type' => 1,])->orderBy('update_time','desc')->first();
        if($endOdds['update_time'] >= $eodds['update_time']){
            return false;
        }

        if(!$endOdds || $endOdds['win'] != $eodds['win'] || $endOdds['lost'] != $eodds['lost']){
            if($eodds['win'] <= 0 || $eodds['lost'] <= 0){
                return false;
            }
            $eodds['odds_type'] = 1;
            $eodds['update_time'] = date('Y-m-d H:i:s');
            self::create($eodds);
            self::update_oddscache($odds['match_id']);
            return true;
        }

        return false;
    }

    public static function update_oddscache($match_id)
    {
        echo "<pre />";
        $a = microtime(true);
        if(!is_numeric($match_id)){
            return false;
        }
        #temp add

        $odds = self::where(['match_id' => $match_id,])->orderBy('bookmaker_id')->get()->toArray();

        if(!$odds){
            return false;
        }
        $bookmaker_id = array_column($odds, 'bookmaker_id');
        $updated_at = array_column($odds, 'update_time');
        array_multisort($bookmaker_id, SORT_ASC, $updated_at, SORT_ASC,$odds);

        $book_odds = [];
        foreach ($odds as $key=>$val)
        {
            $book_odds[$val['bookmaker_id']][] = $val;
        }
        $odds = null;

        $books = bookmaker::where('type', 3)->whereIn('bookmaker_id',array_keys($book_odds))->get(['bookmaker_id','bookmaner_name','level'])->toArray();
        $books = reset_key($books,'bookmaker_id');

        $final_odds = [];
        $change_odds = [];
        foreach ($book_odds as $bkey=>$bval)
        {

            $init_odds = $bval[0];
            $init_odds = array_first($bval, function ($key,$value){
                return $value['odds_type'] == 0;
            });

            $lasted_odds = array_last($bval, function ($key,$value){
                return $value['odds_type'] == 1;
            });

            $last_secondodds = array_last($bval, function ($key,$value) use($bkey,$lasted_odds){
                return $value['odds_type'] == 1 && $value['update_time'] < $lasted_odds['update_time'];
            });

            $lasted_odds = $lasted_odds ? $lasted_odds : $init_odds;

            $last_secondodds = $last_secondodds ? $last_secondodds : $init_odds;

            $final_odds[$bkey] = [
                'init_match_id' => $init_odds['match_id'],
                'init_win' => number_format($init_odds['win'],2),
                'init_lost' => number_format($init_odds['lost'],2),
                'init_odds_type' => $init_odds['odds_type'],
                'init_update_time' => $init_odds['update_time'],
                'bookmaker_id' => $init_odds['bookmaker_id'],
                'win' => number_format($lasted_odds['win'],2),
                'lost' => number_format($lasted_odds['lost'],2),
                'update_time' => $lasted_odds['update_time'],
                'odds_type' => $lasted_odds['odds_type'],
                'bookmaker_name' => isset($books[$init_odds['bookmaker_id']]) ? $books[$init_odds['bookmaker_id']]['bookmaner_name'] : '',

                'win_sj' => $lasted_odds ? ($lasted_odds['win'] > $init_odds['win'] ? 'up' : ($lasted_odds['win'] < $init_odds['win'] ? 'down' : 'unchanged')) : 'unchanged',

                'lost_sj' => $lasted_odds ? ($lasted_odds['lost'] > $init_odds['lost'] ? 'up' : ($lasted_odds['lost'] < $init_odds['lost'] ? 'down' : 'unchanged')) : 'unchanged',

                'win_sj_ratio' => ( $init_odds['win'] - $lasted_odds['win'] != 0 ) ? sprintf('%.1f', abs(( $init_odds['win'] - $lasted_odds['win'] ) / $init_odds['win'] * 100)) : 0,

                'lost_sj_ratio' => ( $init_odds['lost'] - $lasted_odds['lost'] != 0 ) ? sprintf('%.1f', abs(( $init_odds['lost'] - $lasted_odds['lost'] ) / $init_odds['lost'] * 100)) : 0,
            ];

            ##
            foreach ($bval as $okey=>$oval)
            {
                ##
                if($oval['win'] == 0 ||  $oval['lost'] == 0){
                    continue ;
                }
                $change_odds[$bkey][$okey] = $oval;

                if($okey != 0){

                    $change_odds[$bkey][$okey]['win_sj'] = ($oval['win'] > $bval[$okey-1]['win']) ? 'up' : (($oval['win'] < $bval[$okey - 1]['win']) ? 'down' : 'unchanged');
                    $change_odds[$bkey][$okey]['lost_sj'] = ($oval['lost'] > $bval[$okey-1]['lost']) ? 'up' : (($oval['lost'] < $bval[$okey - 1]['lost']) ? 'down' : 'unchanged');
                }else{
                    $change_odds[$bkey][$okey]['win_sj'] = 'unchanged';
                    $change_odds[$bkey][$okey]['lost_sj'] = 'unchanged';
                }

                $updated_time = array_column($change_odds[$bkey], 'update_time');
                array_multisort($updated_time, SORT_DESC,$change_odds[$bkey]);
            }

        }
        #
        $final_odds = reset_key($final_odds, 'bookmaker_id');


        $live_odds = $final_odds;
        ##
        foreach ($live_odds as $key=>$val)
        {
            $live_odds[$key] = json_encode($val);
        }

        $final_odds = json_encode($final_odds);
        foreach ($change_odds as $key=>$val)
        {
            $change_odds[$key] = json_encode($val);
        }

        Predis::set(sprintf(self::$odds_basic_key, $match_id),$final_odds);

        Predis::expire(sprintf(self::$odds_basic_key, $match_id),3*365*24*3600);
        $change_odds && Predis::hmset(sprintf(self::$odds_change_key, $match_id),$change_odds);

        Predis::expire(sprintf(self::$odds_change_key, $match_id),3*365*24*3600);

    }

    public static function get_odds($match_ids, $bookmaker_ids=[])
    {

        if(!is_array($match_ids) || empty($match_ids)){
            return [];
        }

        $data = Predis::pipeline(function($line) use ($match_ids, $bookmaker_ids){
            foreach ($match_ids as $key=>$val)
            {
                $key = sprintf(self::$odds_basic_key,$val);

                $line->get($key);
            }

        });

        $data = array_combine($match_ids, $data);

        return $data;
    }

    public function get_change_odds($match_id, $bookmaker_id) {

        if(!$match_id || !$bookmaker_id) {
            return false;
        }

        $key = sprintf(self::$odds_change_key, $match_id);
        $data = Predis::hget($key, $bookmaker_id);

        if(!$data) {
            return false;
        }

        $data = is_array($data) ? $data : json_decode($data, true);

        foreach ($data as $key => $val) {
            $data[$key]['win'] = sprintf('%.2f', $val['win']);

            $data[$key]['lost'] = sprintf('%.2f', $val['lost']);
        }

        return $data;
    }

}