<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\FscoreTable;
use App\models\Fmatch;
use DB;
class FscoretableController extends Controller
{
    
    //联赛积分榜
    public function index(Request $request){
        $league_id = $request->input('league_id') ? $request->input('league_id') :0;
        $season_id = $request->input('season_id') ? $request->input('season_id') :0;
        //'主客队标识 0：全场 1：主场 2：客场'
        $is_host = $request->input('is_host') ? $request->input('is_host') :0;
        $team_id = $request->input('team_id') ? $request->input('team_id') :0;
    	//SELECT id,team_id,team_name,rank,points,total_count,win_count,draw_count,lost_count,get_score,lost_score,color,info FROM d_scoretable WHERE season_id = 985 and is_host=0 and type =0 AND league_id=8
        $row = FscoreTable::where('type', '0');
        if($team_id){
            $row = $row->where('team_id', $team_id);
        }
        $row = $row->where('is_host', $is_host)->where('league_id', $league_id)->where('season_id', $season_id)->select('id','team_id','team_name','rank','points','total_count','win_count','draw_count','lost_count','get_score','lost_score','color','info')->orderBy('rank', 'asc')->get()->toarray();

        return ['code' => 1,'success' => true,'list' => $row];
    }



    //球队积分榜
    public function match(Request $request){
        $data = [];
        //'主客队标识 0：全场 1：主场 2：客场'
        $is_host = $request->input('is_host') ? $request->input('is_host') :0;
        $match_id = $request->input('match_id') ? $request->input('match_id') :0;
        //获取主客队的ID
        $fmatch = Fmatch::where('match_id', $match_id)->select('season_id','league_id','home_id','guest_id')->get()->toarray();
        //SELECT id,team_id,team_name,rank,points,total_count,win_count,draw_count,lost_count,get_score,lost_score,color,info FROM d_scoretable WHERE season_id = 985 and is_host=0 and type =0 AND league_id=8
        $row = FscoreTable::where('type', '0');
        if($fmatch&&$match_id){
            $team_id[0] = $fmatch[0]['home_id'];
            $team_id[1] = $fmatch[0]['guest_id'];
            $rows = $row->wherein('team_id', $team_id);
            $season_id = $fmatch[0]['season_id'];
            $league_id = $fmatch[0]['league_id'];
        
        $total = $rows->where('is_host', $is_host)->where('league_id', $league_id)->where('season_id', $season_id)->select('id','team_id','team_name','rank','points','total_count','win_count','draw_count','lost_count','get_score','lost_score','color','info')->get()->toarray();
        //主场积分
        $home = FscoreTable::where('type', '0')->where('team_id', $fmatch[0]['home_id'])->where('is_host', 1)->where('league_id', $league_id)->where('season_id', $season_id)->select('id','team_id','team_name','rank','points','total_count','win_count','draw_count','lost_count','get_score','lost_score','color','info')->get()->toarray();
        //客场积分
        $guest = FscoreTable::where('type', '0')->where('team_id', $fmatch[0]['guest_id'])->where('is_host', 2)->where('league_id', $league_id)->where('season_id', $season_id)->select('id','team_id','team_name','rank','points','total_count','win_count','draw_count','lost_count','get_score','lost_score','color','info')->get()->toarray();

        }
        $data['total'] = $total;
        $data['home_guest'] = array_merge($home,$guest);
        return ['code' => 1,'success' => true,'list' => $data];
    }


}
