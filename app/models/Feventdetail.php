<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Feventdetail extends Model
{
    protected $table = 'd_detail';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static $event_type = array(
        1 => '入球', 2 => '红牌', 3 => '黄牌',
        7 => '点球', 8 => '乌龙', 9 => '两黄变红',
        11 => '换人', 12 => '射失点球'
    );


    public static function handleData ($out_data) {
        if (!$out_data) {
            return false;
        }
        $event_data = [];
        foreach ($out_data->eventList as $key => $val) {
            $out_match_id = $val->matchId;
            $match_data = Fmatch::where('out_match_id', $out_match_id)->first();
            $match_id = $match_data ? $match_data->toArray()['match_id'] : 0;
            $map = [];
            foreach ($val->event as $e_key => $e_val) {
                $data = [
                    'match_id' => $match_id,
                    'out_match_id' => $out_match_id,
                    'type' => $e_val->kind,
                    'happen_time' => $e_val->time,
                    'player_ids' => ($e_val->playerId ? $e_val->playerId : '').($e_val->playerId2 ? ','.$e_val->playerId2 : ''),
                    'player_name' => $e_val->nameChs,
                ];
                if ($e_val->playerId2) {
                    $data['assist_id'] = $e_val->playerId2;
                }
                if ($e_val->isHome) {
                    $data['is_host'] = 1;
                } else {
                    $data['is_host'] = 0;
                }
                $map[] = $data;
            }
            $event_data[$match_id] = $map;
        }
        return $event_data;
    }
}