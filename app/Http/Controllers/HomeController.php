<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\Country;
use App\models\Fleague;
use App\models\Fseason;
use App\models\Bleague;
use App\models\Bseason;
use App\models\Bgroup;
use App\models\Bsection;
use DB;
class HomeController extends Controller
{


     //热门赛季列表league
    public function league_list(Request $request){
       // \DB::connection()->enableQueryLog();
        //SELECT * FROM `d_league` WHERE `league_name` IN ('英超','意甲','西甲','德甲','法甲','中超','欧冠','亚冠','日职','日亿','美职足','俄超','瑞超','挪超','巴甲')
        $match_type = $request->input('match_type') ? $request->input('match_type') : 1; //比赛类型 1：足球 2：篮球
        if($match_type == 1){
            $league_name = "英超,意甲,西甲,德甲,法甲,中超,欧冠,亚冠,日职,日亿,美职足,俄超,瑞超,挪超,巴甲";
            $league = Fleague::whereIn('league_name', explode(',', $league_name))->select('league_id','logo_path','league_name')->get()->toarray();
        }
        if($match_type == 2){
            $league_name = "NBA,CBA";
            $league = Bleague::whereIn('league_name', explode(',', $league_name))->select('league_id','logo_path','league_name')->get()->toarray();
        }
        // $log = DB::getQueryLog($league);
       // dd($log);
        return ['code' => 1,'success' => true,'list' => $league];
    }


    //赛季表
    public function season_list(Request $request){
        $match_type = $request->input('match_type') ? $request->input('match_type') : 1; //比赛类型 1：足球 2：篮球
        $league_id = $request->input('league_id') ? $request->input('league_id') :0;
        if($match_type==1){
            $season = Fseason::where('league_id', $league_id)->where('season_name','<>', '')->select('season_id','season_name')->get()->toarray();
        }
        if($match_type==2){
            $season = Bseason::where('league_id', $league_id)->select('id as season_id','season_name')->get()->toarray();
        }
 
        return ['code' => 1,'success' => true,'list' => $season];
    }
    
    //篮球查询赛季下的阶段
    public function section_list(Request $request){
        $season_id = $request->input('season_id') ? $request->input('season_id') :0;
         //查赛事阶段
        //SELECT * FROM d_bsection WHERE season_id=1;
        $Bsection = Bsection::where('season_id', $season_id)->select('id','section_name')->get()->toarray();
        return ['code' => 1,'success' => true,'list' => $Bsection];
        
    }
    

    //篮球查询赛季下的阶段
    public function group_list(Request $request){
         $section_id = $request->input('section_id') ? $request->input('section_id') :0;
         //查询分组
        //SELECT * FROM d_bgroup WHERE section_id=1;
        $Bgroup = Bgroup::where('section_id', $section_id)->select('id','group_name')->get()->toarray();
        return ['code' => 1,'success' => true,'list' => $Bgroup];
    }
    


}
