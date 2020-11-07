<?php

namespace App\Http\Controllers;

use App\models\Bstatistics;
use App\models\Bstatisteam;
use App\models\Bstatisplayer;
use Illuminate\Http\Request;
use App\models\Bmatch;

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
        $out_match_id = $request->input('out_match_id') ? $request->input('out_match_id') : 0;
        $data = [];
        if ($out_match_id) {
            $match_data = Bmatch::where('out_match_id', $out_match_id)->first();
            $match_id = $match_data ? $match_data->id : false;
        }
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


     //球员技术排名
    //#  `shoot` int(11) DEFAULT '0' COMMENT '投篮数',
 //# `score` int(11) DEFAULT '0' COMMENT '得分',
//#  `helpattack` int(11) DEFAULT '0' COMMENT '助攻次数',

    public function StatisplayerShoot(Request $request){
        $data = [];
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        if($match_id){
            $bmatch = Bmatch::where('id', $match_id)->select('home_id','away_id')->get()->toarray();
            //主队投篮数
            //select player_name,score from d_bstatisplayer where team_id=65 order by score desc limit 1;
            $home_score = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['home_id'])->select('d_bplayer.player_name','score','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('score', 'desc')->get()->toarray();
            //客队
            $away_score = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['away_id'])->select('d_bplayer.player_name','score','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('score', 'desc')->get()->toarray();
            //得分
            //select player_name,shoot from d_bstatisplayer where team_id=65 order by shoot desc limit 1;
            $home_shoot = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['home_id'])->select('d_bplayer.player_name','shoot','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('shoot', 'desc')->get()->toarray();
            $away_shoot = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['away_id'])->select('d_bplayer.player_name','shoot','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('shoot', 'desc')->get()->toarray();
            //助攻次数
            //select player_name,helpattack from d_bstatisplayer where team_id=65 order by helpattack desc limit 1;
            $home_helpattack = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['home_id'])->select('d_bplayer.player_name','helpattack','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('helpattack', 'desc')->get()->toarray();
            $away_helpattack = Bstatisplayer::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bstatisplayer.team_id', $bmatch[0]['away_id'])->select('d_bplayer.player_name','helpattack','d_bplayer.photo','d_bplayer.number')->skip(0)->take(1)->orderBy('helpattack', 'desc')->get()->toarray();
            if($home_score&&$home_shoot&&$home_helpattack&&$away_score&&$away_shoot&&$away_helpattack){
                $data['home']['score'] = $home_score?$home_score[0]:[];
                $data['home']['shoot'] = $home_shoot?$home_shoot[0]:[];
                $data['home']['helpattack'] = $home_helpattack?$home_helpattack[0]:[];

                $data['away']['score'] = $away_score?$away_score[0]:[];
                $data['away']['shoot'] = $away_shoot?$away_shoot[0]:[];
                $data['away']['helpattack'] = $away_helpattack?$away_helpattack[0]:[];
            }
        }
        return ['code' => 1,'success' => true,'list' => $data];
        
    }


}
