<?php

namespace App\Http\Controllers;

use App\models\Bstatistics;
use App\models\Bstatisteam;
use App\models\Bstatisplayer;
use Illuminate\Http\Request;

use DB;
class BstatisticsController extends Controller
{
    
//赛季
    public function index(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $out_match_id = $request->input('out_match_id') ? $request->input('out_match_id') : 0;
        $data = [];
        if ($match_id) {
                $data = Bstatistics::where('match_id', $match_id)->get()->toarray();
            } else if ($out_match_id) {
                $data = Bstatistics::where('out_match_id', $out_match_id)->get()->toarray();
            } else {
                return ['code' => 0,'success' => false,'list' => [], 'message' => '参数无效'];
            }
        //1636
        return ['code' => 1,'success' => true,'list' => $data];
    }


 
    //球队1636
    public function Bstatis_team(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $data = [];
        if ($match_id) {
                $data = Bstatisteam::where('match_id', $match_id)->get()->toarray();
            } else {
                return ['code' => 0,'success' => false,'list' => [], 'message' => '参数无效'];
            }
        //1636
        return ['code' => 1,'success' => true,'list' => $data];
    }


    //球员1636
    public function Bstatisplayer(Request $request){
        //  `team_id` int(11) DEFAULT '0' COMMENT '球队ID',
        //`player_id` int(11) DEFAULT '0' COMMENT '球员ID',
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $team_id = $request->input('team_id') ? $request->input('team_id') : 0;
       // $player_id = $request->input('player_id') ? $request->input('player_id') : 0;
        $data = [];

        if ($team_id&&$match_id) {
                $data = Bstatisplayer::where('team_id', $team_id)->where('match_id', $match_id)->get()->toarray();
            }elseif(!$team_id&&$match_id){
                $data = Bstatisplayer::where('match_id', $match_id)->get()->toarray();
            }elseif($team_id&&!$match_id){
                $data = Bstatisplayer::where('team_id', $team_id)->get()->toarray();
            }else {
                return ['code' => 0,'success' => false,'list' => [], 'message' => '参数无效'];
            }
        //1636
        return ['code' => 1,'success' => true,'list' => $data];
    }


}
