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

class Btotalhandicap extends Model
{
    //
    protected $table = 'd_btotalhandicap';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static $odds_basic_key = 'BMATCH:BASIC:DXF:KEY:%s';

    public static $odds_change_key = 'BMATCH:CHANGE:DXF:KEY:%s';

    static $zf = [
        4 => '澳门',
        5 => '易胜博',
        6 => '皇冠',
        11 => 'Bet365',
        12 => '韦德',
        34 => '利记',
    ];

    /**
     *
     * @param array $odds
     * @return boolean
     */
    public static function compareInsert($odds, $eodds = [])
    {

        $eodds = $eodds ? $eodds : $odds;
        $startOdds = self::where(['match_id' => $odds['match_id'], 'bookmaker_id' => $odds['bookmaker_id'], 'odds_type' => 0,])->first();

        if (!$startOdds) {
            self::create($odds);
        }
        $endOdds = self::where(['match_id' => $odds['match_id'], 'bookmaker_id' => $odds['bookmaker_id'], 'odds_type' => 1,])->orderBy('update_time', 'desc')->first();

        if ($endOdds['update_time'] >= $eodds['update_time']) {
            return false;
        }

        if (!$endOdds || $endOdds['big_ball'] != $eodds['big_ball'] || $endOdds['small_ball'] != $eodds['small_ball']) {

            if ($eodds['big_ball'] <= 0 || $eodds['small_ball'] <= 0) {
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

    /**
     *
     * @param array $out_data
     * @return boolean|multitype:array
     */

    public static function convert_qtOdds($out_data)
    {
        echo "<pre>";
        $source = 'win007';
        if(!isset($out_data) || empty($out_data)){
            return false;
        }

        foreach ( $out_data as $key => $val ) {
            $match_id = $val[0];
            $out_bookMaker_id = $val[1];
            $shand = $val[2];
            $swin = $val[3];
            $slost = $val[4];
            $hand = $val[5];
            $win = $val[6];
            $lost = $val[7];
//            list($match_id, $out_bookMaker_id, $shand, $swin, $slost, $hand, $win, $lost) = explode(',', $val);
            $match_data[$match_id]['out_match_id'] = $match_id;
            $match_data[$match_id]['out_bookmaker_id'] = $out_bookMaker_id;
            $match_data[$match_id]['shand'] = $shand;
            $match_data[$match_id]['swin'] = $swin;
            $match_data[$match_id]['slost'] = $slost;
            $match_data[$match_id]['hand'] = $hand;
            $match_data[$match_id]['win'] = $win;
            $match_data[$match_id]['lost'] = $lost;

        }

        $out_matchids = array_column($match_data, 'out_match_id');

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

        $book_data = bookmaker::where(['type' => 5])->get(['bookmaker_id','out_bookmaker_id'])->toArray();
        $bookid_maps = array_column($book_data, 'bookmaker_id','out_bookmaker_id');

        $odds = [];

        foreach ($Match_odds as $mkey => $mval)
        {
            $match_id = (int) $match_ids[$mval['out_match_id']];
            $s_win = $mval['swin'];
            $s_lost = $mval['slost'];
            $win = $mval['win'];
            $lost = $mval['lost'];
            $s_hand = $mval['shand'];
            $hand = $mval['hand'];
            $out_bookMaker_id = $mval['out_bookmaker_id'];

            if(!$match_id){
                continue ;
            }
            ##比赛外部博彩id
            $out_bookMaker_Ids[] = $mval['out_bookmaker_id'];

            $bookMakerId = (int) isset($bookid_maps[$out_bookMaker_id]) ? $bookid_maps[$out_bookMaker_id] : 0;

//            $bookmaner_name = isset(self::$bookmaker_map[$out_bookMaker_id]) ? self::$bookmaker_map[$out_bookMaker_id] : '';

            if ( $bookMakerId == 0 ) {
                $bookMakerId = self::save_bookemaker($out_bookMaker_id, self::$zf[$out_bookMaker_id], 5);
            }

            $odds[] = [
                'start' =>[
                    'match_id' => (int) $match_id,
                    'bookmaker_id' => (int) $bookMakerId,
                    'out_match_id' => (int) $mval['out_match_id'],
                    'out_bookmaker_id' => (int) $mval['out_bookmaker_id'],
                    'odds_type' => 0,
                    'handicap_num' => $s_hand,
                    'big_ball' => $s_win,
                    'small_ball' => $s_lost,
                    'add_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ],
                'end' => [
                    'match_id' => (int) $match_id,
                    'bookmaker_id' => (int) $bookMakerId,
                    'out_match_id' => (int) $mval['out_match_id'],
                    'out_bookmaker_id' => (int) $mval['out_bookmaker_id'],
                    'odds_type' => 1,
                    'handicap_num' => $hand,
                    'big_ball' => $win,
                    'small_ball' => $lost,
                    'add_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ],
            ];

        }

        return $odds;
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

    public static function get_odds($match_ids, $bookmaker_ids=[])
    {

        if (!is_array($match_ids) || empty($match_ids)) {
            return [];
        }

        $data = Predis::pipeline(function ($line) use ($match_ids, $bookmaker_ids) {
            foreach ($match_ids as $key => $val) {
                $key = sprintf(self::$odds_basic_key, $val);
                $line->get($key);
            }
        });

        $data = array_combine($match_ids, $data);

        return $data;
    }

    public static function update_oddscache($match_id)
    {
        echo "<pre />";
        $a = microtime(true);
        if (!is_numeric($match_id)) {
            return false;
        }
        $odds = self::where(['match_id' => $match_id,])->get()->toArray();
        if (!$odds) {
            return false;
        }

        $bookmaker_id = array_column($odds, 'bookmaker_id');

        $updated_at = array_column($odds, 'update_time');
        array_multisort($bookmaker_id, SORT_ASC, $updated_at, SORT_ASC, $odds);

        $book_odds = [];
        foreach ($odds as $key => $val) {
            $book_odds[$val['bookmaker_id']][] = $val;
        }
        $odds = null;

        $odds = self::where(['match_id' => $match_id,])->orderBy('handicap_num')->groupBy(['bookmaker_id', 'handicap_num'])->get()->toArray();

        foreach ($odds as $key => $val) {
            $more_odds[$val['bookmaker_id']][] = $val;
        }

        $books = bookmaker::whereIn('bookmaker_id', array_keys($book_odds))->get(['bookmaker_id', 'bookmaner_name'])->toArray();
        $books = reset_key($books, 'bookmaker_id');

        $final_odds = $change_odds = [];

        foreach ($book_odds as $bkey => $bval) {

            $init_odds = array_first($bval, function ($key, $value) {
                return $value['odds_type'] == 0;
            });
            if (!$init_odds) {
                $init_odds = $bval[0];
            }

            $lasted_odds = array_last($bval, function ($key, $value) {
                return $value['odds_type'] == 1;
            });

            $last_secondodds = array_last($bval, function ($key, $value) use ($lasted_odds) {
                return $value['odds_type'] == 1 && $value['update_time'] < $lasted_odds['update_time'];
            });

            if (!$lasted_odds) {
                $last_data = array_reverse($bval);
                $lasted_odds = $last_data[0];
                $last_secondodds = $last_data[0];
            }

            $final_odds[$bkey] = [
                'match_id'       => $match_id,
                'bookmaker_id'   => $init_odds['bookmaker_id'],
                'bookmaner_name' => isset($books[$init_odds['bookmaker_id']]) ? $books[$init_odds['bookmaker_id']]['bookmaner_name'] : '',

                'i_handicap_num' => $init_odds['handicap_num'],
                'i_big_ball'     => number_format($init_odds['big_ball'], 2),
                'i_small_ball'   => number_format($init_odds['small_ball'], 2),
                'i_updated_time'   => $init_odds['update_time'],

                'handicap_num'   => $lasted_odds['handicap_num'],
                'big_ball'       => number_format($lasted_odds['big_ball'], 2),
                'small_ball'     => number_format($lasted_odds['small_ball'], 2),
                'update_time'     => $lasted_odds['update_time'],

                'num_sj'         => $lasted_odds ? ($lasted_odds['handicap_num'] > $init_odds['handicap_num'] ? 'up' : ($lasted_odds['handicap_num'] < $init_odds['handicap_num'] ? 'down' : 'unchanged')) : 'unchanged',
                'big_ball_sj'    => $lasted_odds ? ($lasted_odds['big_ball'] > $init_odds['big_ball'] ? 'up' : ($lasted_odds['big_ball'] < $init_odds['big_ball'] ? 'down' : 'unchanged')) : 'unchanged',
                'small_ball_sj'  => $lasted_odds ? ($lasted_odds['small_ball'] > $init_odds['small_ball'] ? 'up' : ($lasted_odds['small_ball'] < $init_odds['small_ball'] ? 'down' : 'unchanged')) : 'unchanged',

                'num_sj_ratio' => ( $init_odds['handicap_num'] - $lasted_odds['handicap_num'] != 0 ) ? sprintf('%.1f', abs(( $init_odds['handicap_num'] - $lasted_odds['handicap_num'] ) / $init_odds['handicap_num'] * 100)) : 0,
                'big_ball_sj_ratio' => ( $init_odds['big_ball'] - $lasted_odds['big_ball'] != 0 ) ? sprintf('%.1f', abs(( $init_odds['big_ball'] - $lasted_odds['big_ball'] ) / $init_odds['big_ball'] * 100)) : 0,

                'small_ball_sj_ratio' => ( $init_odds['small_ball'] - $lasted_odds['small_ball'] != 0 ) ? sprintf('%.1f', abs(( $init_odds['small_ball'] - $lasted_odds['small_ball'] ) / $init_odds['small_ball'] * 100)) : 0,
            ];

            foreach ($bval as $okey=>$oval)
            {
                $change_odds[$bkey][$okey] = $oval;

                if($okey != 0){

                    $change_odds[$bkey][$okey]['num_sj'] = ($oval['handicap_num'] > $bval[$okey-1]['handicap_num']) ? 'up' : (($oval['handicap_num'] < $bval[$okey - 1]['handicap_num']) ? 'down' : 'unchanged');
                    $change_odds[$bkey][$okey]['big_ball_sj'] = ($oval['big_ball'] > $bval[$okey-1]['big_ball']) ? 'up' : (($oval['big_ball'] < $bval[$okey - 1]['big_ball']) ? 'down' : 'unchanged');
                    $change_odds[$bkey][$okey]['small_ball_sj'] = ($oval['small_ball'] > $bval[$okey-1]['small_ball']) ? 'up' : (($oval['small_ball'] < $bval[$okey - 1]['small_ball']) ? 'down' : 'unchanged');
                }else{
                    $change_odds[$bkey][$okey]['num_sj'] = 'unchanged';
                    $change_odds[$bkey][$okey]['big_ball_sj'] = 'unchanged';
                    $change_odds[$bkey][$okey]['small_ball_sj'] = 'unchanged';
                }

                ##
                $updated_time = array_column($change_odds[$bkey], 'update_time');
                array_multisort($updated_time, SORT_DESC,$change_odds[$bkey]);
            }

            $updated_time = array_column($change_odds[$bkey], 'update_time');
            array_multisort($updated_time, SORT_DESC, $change_odds[$bkey]);
        }

        $final_odds = reset_key($final_odds, 'bookmaker_id');

        $final_odds = json_encode($final_odds);

        foreach ($change_odds as $key => $val) {
            $change_odds[$key] = json_encode($val);
        }

        Predis::set(sprintf(self::$odds_basic_key, $match_id), $final_odds);
        Predis::expire(sprintf(self::$odds_basic_key, $match_id), 3 * 365 * 24 * 3600);

        Predis::hmset(sprintf(self::$odds_change_key, $match_id), $change_odds);
        Predis::expire(sprintf(self::$odds_change_key, $match_id), 3 * 365 * 24 * 3600);


        $b = microtime(true);
        echo $b - $a . "\n";

        return true;

    }

    public static function get_change_odds($match_id, $bookmaker_id)
    {
        if (!$match_id || !$bookmaker_id) {
            return false;
        }

        $key = sprintf(self::$odds_change_key, $match_id);
        $data = Predis::hget($key, $bookmaker_id);

        if (!$data) {
            return false;
        }

        $data = is_array($data) ? $data : json_decode($data, true);

        foreach ($data as $key => $val) {
            $data[$key]['over'] = sprintf('%.2f', $val['over']);
            $data[$key]['under'] = sprintf('%.2f', $val['under']);
        }

        return $data;


    }

}