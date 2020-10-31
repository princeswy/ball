<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-31
 * Time: 21:57
 */

namespace App\Http\Controllers;


use App\models\bookmaker;
use App\models\Fodds;
use Illuminate\Http\Request;

class FoddsController extends Controller
{
    public function odds_list(Request $request) {
        $match_id = $request->input('match_id') ? $request->input('match_id') : false;
        $ret = [
            'code' => 1,
            'success' => true,
            'message' => '获取成功',
            'list' => []
        ];
        if (!$match_id) {
            $ret['code'] = 0;
            $ret['success'] = false;
            $ret['message'] = '参数有误，match_id为必传参数';
            return $ret;
        }
        $bookmaker_map = bookmaker::where('type', 1)->orderBy('level', 'desc')->limit(50)->get()->toArray();
        $bookmakerid_map = array_column($bookmaker_map, 'bookmaker_id');
        $bookmaker_name_map = array_column($bookmaker_map, 'bookmaner_name', 'bookmaker_id');
        $start_odds_map = Fodds::where(['match_id' => $match_id, 'odds_type' => 0])->whereIn('bookmaker_id', $bookmakerid_map)->get();
        if (!$start_odds_map) {
            $ret['code'] = 2;
            $ret['message'] = '暂无数据';
            return $ret;
        }
        $odds_list = [];
//        $start_odds_data = $start_odds_map->toArray();
        foreach ($bookmaker_name_map as $key => $val) {
            $res = [
                'match_id' => $match_id,
                'bookmaker_id' => $key,
                'bookmaker_name' => $val,
                'start' => [],
                'end' => []
            ];
            $start_odds = Fodds::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 0])->first();
            if (!$start_odds) {
                continue;
            }
            $start_odds_data = $start_odds->toArray();
            $res['start']['win'] = sprintf("%.2f",$start_odds_data['win']);
            $res['start']['draw'] = sprintf("%.2f",$start_odds_data['draw']);
            $res['start']['lost'] = sprintf("%.2f",$start_odds_data['lost']);
            $end_odds = Fodds::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 1])->first();
            if ($end_odds) {
                $end_odds_data = $end_odds->toArray();
                $res['end']['win'] = sprintf("%.2f",$end_odds_data['win']);
                $res['end']['draw'] = sprintf("%.2f",$end_odds_data['draw']);
                $res['end']['lost'] = sprintf("%.2f",$end_odds_data['lost']);
            } else {
                $res['end']['win'] = sprintf("%.2f",$start_odds_data['win']);
                $res['end']['draw'] = sprintf("%.2f",$start_odds_data['draw']);
                $res['end']['lost'] = sprintf("%.2f",$start_odds_data['lost']);
            }
            $ret['list'][] = $res;
        }
        return $ret;
    }
}