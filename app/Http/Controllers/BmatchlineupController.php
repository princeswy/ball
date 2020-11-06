<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\models\Bmatchlineup;
use DB;
class BmatchlineupController extends Controller
{


     //篮球阵容表
     ///DB::connection()->enableQueryLog(); 
     /////  $log = DB::getQueryLog();
     //dd($log);
    public function index(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $data = array();
        //首发阵容
            $matchLineup = Bmatchlineup::join('d_bplayer','d_bplayer.id', '=', 'player_id')->where('d_bmatch_lineup.match_id',  $match_id)->select('d_bmatch_lineup.is_sf','d_bmatch_lineup.is_host','d_bmatch_lineup.player_name','d_bplayer.number as player_number','d_bplayer.photo as log','d_bmatch_lineup.player_id', 'd_bmatch_lineup.position')->get()->toarray();
           // dd($matchLineup);
            foreach ($matchLineup as $k => $v) {
                //主队收发
                if($v['is_sf']==1&&$v['is_host']==1){
                    $data['home_Lineup'][]=$v;
                }elseif($v['is_sf']==1&&$v['is_host']==2){//客队首发
                    $data['guest_Lineup'][]=$v;
                }elseif($v['is_sf']==2&&$v['is_host']==1){//主队替补
                    $data['home_replace'][]=$v;
                }elseif($v['is_sf']==2&&$v['is_host']==2){//客队替补
                    $data['guest_replace'][]=$v;
                }
            }
       
        return ['code' => 1,'success' => true,'list' => $data];
    }






}
