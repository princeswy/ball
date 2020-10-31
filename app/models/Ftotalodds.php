<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Ftotalodds extends Model
{
    protected $table = 'd_total_odds';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function handleData ($where, $data) {
        $id = self::updateOrCreate($where, $data)->id;
        return $id;
    }

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

        $Match_odds = $out_data->overUnder;

        $now_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s',time()-5);
        $odds = [];
        foreach ($Match_odds as $mkey => $mval)
        {
            $out_matchid = $mval[0];
            $out_bookMaker_id = $mval[1];
            $init_handicap_num = $mval[2];
            $s_over = $mval[3];
            $s_under = $mval[4];
            $handicap_num = $mval[5];
            $over = $mval[6];
            $under = $mval[7];
            $zoudi = $mval[9];
            if($zoudi){
                continue;
            }

            $match_id = isset($match_ids[$out_matchid]) ? $match_ids[$out_matchid] : 0;
            /*if(!$match_id){
                continue ;
            }*/
            $bookMakerId = isset($bookid_maps[$out_bookMaker_id]) ? $bookid_maps[$out_bookMaker_id] : false;
            if(!$bookMakerId){
                continue ;
            }
//            $init_handicap_num = $init_handicap_num * -1;
//            $handicap_num = $handicap_num * -1;
            $odds[] = [
                'start' => [
                    'match_id' => $match_id,
                    'out_match_id' => $out_matchid,
                    'bookmaker_id' => $bookMakerId,
                    'handicap_num' => $init_handicap_num,
                    'odds_type' => 0,
                    'over' => $s_over,
                    'under' => $s_under,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                'end' => [
                    'match_id' => $match_id,
                    'out_match_id' => $out_matchid,
                    'bookmaker_id' => $bookMakerId,
                    'handicap_num' => $handicap_num,
                    'odds_type' => 1,
                    'over' => $over,
                    'under' => $under,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        return $odds;
    }

    public static function compareInsert($odds, $eodds)
    {

        $eodds = $eodds ? $eodds : $odds;
        $startOdds = self::where(['match_id' => $odds['match_id'],'out_match_id' => $odds['out_match_id'],'bookmaker_id' => $odds['bookmaker_id'], 'handicap_num' => $odds['handicap_num'],'odds_type' => 0])->first();
        if(!$startOdds && is_array($odds)){
            Ftotalodds::create($odds);
        }
        $endOdds = self::where(['match_id' => $odds['match_id'],'out_match_id' => $odds['out_match_id'],'bookmaker_id' => $odds['bookmaker_id'],'odds_type' => 1])->orderBy('updated_at','desc')->first()->toArray();
        if($endOdds['updated_at'] >= $eodds['updated_at']){
            return false;
        }

        if(!$endOdds || $endOdds['over'] != $eodds['over'] || $endOdds['under'] != $eodds['under'] || $endOdds['handicap_num'] != $eodds['handicap_num']){
            if(is_array($eodds)){
                $eodds['odds_type'] = 1;
                Ftotalodds::create($eodds);
                return true;
            }else{
                return false;
            }

        }
        return false;
    }
}