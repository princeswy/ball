<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\Country;
use App\models\Fleague;
use App\models\Fseason;
use DB;
class HomeController extends Controller
{


     //热门赛季列表league
    public function league_list(Request $request){
       // \DB::connection()->enableQueryLog();
        //SELECT * FROM `d_league` WHERE `league_name` IN ('英超','意甲','西甲','德甲','法甲','中超','欧冠','亚冠','日职','日亿','美职足','俄超','瑞超','挪超','巴甲')
        $league_name = "英超,意甲,西甲,德甲,法甲,中超,欧冠,亚冠,日职,日亿,美职足,俄超,瑞超,挪超,巴甲";
        $league = Fleague::whereIn('league_name', explode(',', $league_name))->select('league_id','league_name')->get()->toarray();
        // $log = DB::getQueryLog($league);
       // dd($log);
        return ['code' => 1,'success' => true,'list' => $league];
    }


    //赛季表
    public function season_list(Request $request){
        $league_id = $request->input('league_id') ? $request->input('league_id') :0;

        $season = Fseason::where('league_id', $league_id)->where('season_name','<>', '')->select('season_id','season_name')->get()->toarray();
 
        return ['code' => 1,'success' => true,'list' => $season];
    }
    



}
