<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-27
 * Time: 11:10
 */

namespace App\Http\Controllers;


use App\models\Fmatch;
use Illuminate\Http\Request;

class FmatchController extends Controller
{

//
    public function show(Request $request) {
        $date = date('Y-m-d');
        $match_time = $request->input('match_time') ? $request->input('match_time') : $date;
        $league_name = $request->input('league_name') ? $request->input('league_name') : false;
        $dateMap = [
            date("Y-m-d",strtotime("-1 day"))
        ];
        for ($i = 0; $i < 4; $i++) {
            $dateMap[] = date("Y-m-d",strtotime("+".$i." day"));
        }
        $res = [
            'code' => 1,
            'success' => true,
            'dateList' => $dateMap,
            'curDate' => $match_time,
            'matchState' => [
                0 => '未开赛',
                1 => '上半场',
                2 => '中场',
                3 => '下半场',
                4 => '加时',
                5 => '点球',
                '-1' => '完场',
                '-10' => '取消',
                '-11' => '待定',
                '-12' => '腰斩',
                '-13' => '中断',
                '-14' => '推迟',
            ],
            'leagueList' => []
        ];
        $fmatch = Fmatch::where('match_time', 'like', $match_time.'%');
        if ($league_name) {
            $fmatch = $fmatch->Where('league_name', $league_name);
        }
        $match_map = $fmatch->orderBy('match_time', 'asc')->select('match_id','league_name','match_time','home_name','guest_name','match_state','half_score','score','home_red','guest_red','home_yellow','guest_yellow','home_corner','guest_corner')->get()->toarray();
        $res['list'] = $match_map;
        if (count($match_map) > 0) {
            $leagueMap = Fmatch::where('match_time', 'like', $match_time.'%')->get(['league_name'])->toArray();
            $res['leagueList'] = array_unique(array_column($leagueMap, 'league_name'));
        }
        return $res;
    }
}