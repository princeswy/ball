<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\models\Bmatch;

use DB;
class BmatchController extends Controller
{



    //分组下的月份赛事
    public function Month_data(Request $request){
        $data = array();
        $league_id = $request->input('league_id') ? $request->input('league_id') : 0;
        $season_id = $request->input('season_id') ? $request->input('season_id') : 0;
        $group_id = $request->input('group_id') ? $request->input('group_id') : 0;

        //查询该分组下的比赛
        $sql = "SELECT DATE_FORMAT(match_time, '%Y-%m') as match_month FROM d_bmatch WHERE league_id=".$league_id."  AND season_id =".$season_id." AND group_id=".$group_id." GROUP BY match_month";
       // $data = Bmatch::where('season_id', $season_id)->where('league_id', $league_id)->where('group_id',$group_id)->select(DB::raw("DATE_FORMAT(match_time, '%Y-%m') "))->GROUPBy('match_time')->get()->toarray();
       $data = DB::select($sql);

//var_dump($data);
        return ['code' => 1,'success' => true,'list' => $data];

    }



     //篮球赛程赛果
     ///DB::connection()->enableQueryLog(); 
     /////  $log = DB::getQueryLog(); 
     //dd($log);
    public function index(Request $request){
        $data = array();
        $league_id = $request->input('league_id') ? $request->input('league_id') : 0;
        $season_id = $request->input('season_id') ? $request->input('season_id') : 0;
        $group_id = $request->input('group_id') ? $request->input('group_id') : 0;

        $month_data = $request->input('month_data') ? $request->input('month_data') : '';
        //查询该分组下的比赛
        if($group_id){
            $Bmatch = Bmatch::where('season_id', $season_id)->where('league_id', $league_id)->where('group_id',$group_id)->where('state','-1');
            if($month_data){
                $Bmatch = $Bmatch->where('match_time','like', $month_data . '%');
            }
            $Bmatch = $Bmatch->select('match_time','away_name','home_name','score')->get()->toarray();
     //   var_dump($Bmatch);
            //拆分
            $w = [ '日' , '一' , '二' , '三' , '四' , '五' , '六' ];
            foreach($Bmatch as $k=>$v){
                $week = substr($v['match_time'],0,10);
                $weekday =  date ( 'w' , strtotime ($week));
                $data[$week]['week'] = $week.' 星期'.$w[$weekday];
                $data[$week]['match'][] = $v;

            }
           // var_dump($data);
        }
        return ['code' => 1,'success' => true,'list' =>array_values($data)];
    }








}
