<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Fshooters;
use DB;
class FshootersController extends Controller
{
    
    //联赛积分榜
    public function index(Request $request){
        $league_id = $request->input('league_id') ? $request->input('league_id') :0;
        $season_id = $request->input('season_id') ? $request->input('season_id') :0;
    	//SELECT d_shooters.player_name,d_shooters.team_name,d_shooters.goals,d_shooters.penalty_goals,d_player.logo FROM d_shooters JOIN d_player on d_shooters.player_id=d_player.id WHERE season_id=985  AND league_id=8 ORDER BY goals desc LIMIT 30

        $row = Fshooters::join('d_player','d_player.id', '=', 'player_id')->where('league_id', $league_id)->where('season_id', $season_id)->select('d_shooters.id','d_shooters.player_name','d_shooters.team_name','d_shooters.goals','d_shooters.penalty_goals','d_player.logo','d_shooters.player_id')->skip(0)->take(30)->orderBy('goals', 'desc')->get()->toarray();

        return ['code' => 1,'success' => true,'list' => $row];
    }



    


}
