<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-31
 * Time: 21:57
 */

namespace App\Http\Controllers;


use App\models\bookmaker;
use App\models\Fhandicap;
use App\models\Fodds;
use App\models\Ftotalodds;
use Illuminate\Http\Request;

class FhandoddsController extends Controller
{
    public function odds_list(Request $request) {
        $match_id = $request->input('match_id') ? $request->input('match_id') : false;
        $odds_type = $request->input('odds_type') ? $request->input('odds_type') : 1;
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
        $bookmaker_map = bookmaker::orderBy('level', 'desc')->get()->toArray();
        $bookmakerid_map = array_column($bookmaker_map, 'bookmaker_id');
        $bookmaker_name_map = array_column($bookmaker_map, 'bookmaner_name', 'bookmaker_id');
        // 让球
        if ($odds_type == 1) {
            $start_odds_map = Fhandicap::where(['match_id' => $match_id, 'odds_type' => 0])->whereIn('bookmaker_id', $bookmakerid_map)->get();
        } else {
            $start_odds_map = Ftotalodds::where(['match_id' => $match_id, 'odds_type' => 0])->whereIn('bookmaker_id', $bookmakerid_map)->get();
        }
        if (!$start_odds_map) {
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
            if ($odds_type == 1) {
                $start_odds = Fhandicap::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 0])->first();
            } else {
                $start_odds = Ftotalodds::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 0])->first();
            }
            if (!$start_odds) {
                continue;
            }
            $start_odds_data = $start_odds->toArray();
            if ($odds_type == 1) {
                $res['start']['home'] = sprintf("%.2f", $start_odds_data['home']);
                $res['start']['handicap_num'] = sprintf("%.2f", $start_odds_data['handicap_num']);
                $res['start']['away'] = sprintf("%.2f", $start_odds_data['away']);
                $end_odds = Fhandicap::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 1])->first();
                if ($end_odds) {
                    $end_odds_data = $end_odds->toArray();
                    $res['end']['home'] = sprintf("%.2f", $end_odds_data['home']);
                    $res['end']['handicap_num'] = sprintf("%.2f", $end_odds_data['handicap_num']);
                    $res['end']['away'] = sprintf("%.2f", $end_odds_data['away']);
                } else {
                    $res['end']['home'] = sprintf("%.2f", $start_odds_data['home']);
                    $res['end']['handicap_num'] = sprintf("%.2f", $start_odds_data['handicap_num']);
                    $res['end']['away'] = sprintf("%.2f", $start_odds_data['away']);
                }
            } else {
                $res['start']['over'] = sprintf("%.2f", $start_odds_data['over']);
                $res['start']['handicap_num'] = sprintf("%.2f", $start_odds_data['handicap_num']);
                $res['start']['under'] = sprintf("%.2f", $start_odds_data['under']);
                $end_odds = Ftotalodds::where(['match_id' => $match_id, 'bookmaker_id' => $key, 'odds_type' => 1])->first();
                if ($end_odds) {
                    $end_odds_data = $end_odds->toArray();
                    $res['end']['over'] = sprintf("%.2f", $end_odds_data['over']);
                    $res['end']['handicap_num'] = sprintf("%.2f", $end_odds_data['handicap_num']);
                    $res['end']['under'] = sprintf("%.2f", $end_odds_data['under']);
                } else {
                    $res['end']['over'] = sprintf("%.2f", $start_odds_data['over']);
                    $res['end']['handicap_num'] = sprintf("%.2f", $start_odds_data['handicap_num']);
                    $res['end']['under'] = sprintf("%.2f", $start_odds_data['under']);
                }
            }
            $ret['list'][] = $res;
        }
        return $ret;
    }
}