<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\FplayerCount;
use DB;
class FplayercountController extends Controller
{
    
    //联赛助攻榜
    public function index(Request $request){
        $league_id = $request->input('league_id') ? $request->input('league_id') :0;
        $season_id = $request->input('season_id') ? $request->input('season_id') :0;
        //SELECT d_player_count.assist,d_player_count.team_id,d_player.id,d_player.player_name,d_player.logo,d_team.team_name FROM d_player_count join d_player on d_player.id=d_player_count.player_id join d_team on d_team.team_id=d_player_count.team_id WHERE d_player_count.season_id = '988' AND d_player_count.league_id = '30' ORDER BY d_player_count.assist DESC LIMIT 30
       // $row = FplayerCount::where('is_home', '0');
        $row = FplayerCount::join('d_player','d_player.id', '=', 'd_player_count.player_id')->join('d_team','d_team.team_id', '=', 'd_player_count.team_id')->where('d_player_count.league_id', $league_id)->where('d_player_count.season_id', $season_id)->select('d_player_count.assist','d_player_count.team_id','d_player.id','d_player.player_name','d_player.logo','d_team.team_name')->skip(0)->take(30)->orderBy('assist', 'desc')->get()->toarray();

        return ['code' => 1,'success' => true,'list' => $row];
    }



    


}
