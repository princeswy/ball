<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-27
 * Time: 11:10
 */

namespace App\Http\Controllers;


use App\models\Fmatch;
use App\models\FmatchLineup;
use App\models\Fmissplayer;
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
            $fmatch = $fmatch->whereIn('league_name', explode(',', $league_name));
        }
        $match_map = $fmatch->orderBy('match_time', 'asc')->select('match_id','league_name','match_time','home_name','guest_name','home_id','guest_id','match_state','half_score','score','home_red','guest_red','home_yellow','guest_yellow','home_corner','guest_corner','league_id','season_id')->get()->toarray();
        $res['list'] = $match_map;
        if (count($match_map) > 0) {
            $leagueMap = Fmatch::where('match_time', 'like', $match_time.'%')->get(['league_name'])->toArray();
            $res['leagueList'] = array_unique(array_column($leagueMap, 'league_name'));
        }
        return $res;
    }




    //伤停
    public function missplayer(Request $request){

        $team_id = $request->input('team_id') ? $request->input('team_id') : false;
        $data = '';
        //查当前赛队的伤残
        if($team_id){
            $data = Fmissplayer::join('d_player','d_player.id', '=', 'player_id')->where('d_missplayer.team_id',$team_id)->select('d_missplayer.player_name','d_missplayer.player_id','d_missplayer.player_status_name','d_player.position','d_player.shirt_num')->get()->toarray();
        }
        return ['code' => 1,'success' => true,'dateList' => $data];
    }



    //阵容
    public function match_lineup(Request $request){
        //1：主队 2：客队
        $is_host = $request->input('is_host') ? $request->input('is_host') : 1;
        //比赛Id
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        //获取类型
        //1：首发 2：替补
        $subsitute= $request->input('subsitute') ? $request->input('subsitute') : 1;
        $data = '';
        if($match_id){
            $data = FmatchLineup::join('d_player','d_player.id', '=', 'player_id')->where('d_match_lineup.match_id',  $match_id)->where('d_match_lineup.subsitute',  $subsitute)->where('d_match_lineup.is_host',  $is_host)->select('d_match_lineup.player_id','d_match_lineup.player_name','d_match_lineup.player_number','d_player.logo')->get()->toarray();
        }

        return ['code' => 1,'success' => true,'dateList' => $data];
    }

}