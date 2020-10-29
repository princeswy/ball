<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fodds extends Model
{
    protected $table = 'd_nedlodds';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function handleData ($where, $data) {
        $id = self::updateOrCreate($where, $data)->id;
        return $id;
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
            Fodds::create($odds);
        }
        $endOdds = self::where(['match_id' => $odds['match_id'],'bookmaker_id' => $odds['bookmaker_id'],'odds_type' => 1,])->orderBy('update_time','desc')->first();
        if($endOdds['update_time'] >= $eodds['update_time']){
            return false;
        }

        if(!$endOdds || $endOdds['win'] != $eodds['win'] || $endOdds['draw'] != $eodds['draw'] || $endOdds['lost'] != $eodds['lost']){
            if($eodds['win'] <= 0 || $eodds['draw'] <= 0 || $eodds['lost'] <= 0){
                return false;
            }
            $eodds['odds_type'] = 1;
            $eodds['update_time'] = date('Y-m-d H:i:s');
            Fodds::create($eodds);
            return true;
        }
        return false;
    }
}