<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FscoreTable;
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
        $row = $row->where('is_host', $is_host)->where('league_id', $league_id)->where('season_id', $season_id)->select('id','team_id','team_name','rank','points','total_count','win_count','draw_count','lost_count','get_score','lost_score','color','info')->get()->toarray();

        return ['code' => 1,'success' => true,'dateList' => $row];
    }



    


}
