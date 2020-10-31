<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-27
 * Time: 11:10
 */

namespace App\Http\Controllers;


use App\models\Fmatch;
use App\models\FmatchLineup;
use App\models\Fmissplayer;
use App\Models\Fteam;
use Illuminate\Http\Request;

class FmatchController extends Controller
{

//
    public function show(Request $request) {
        $date = date('Y-m-d');
        $match_time = $request->input('match_time') ? $request->input('match_time') : $date;
        $league_id = $request->input('league_id') ? $request->input('league_id') : false;
        $dateMap = [
            date("Y-m-d",strtotime("-1 day"))
        ];
        for ($i = 0; $i < 4; $i++) {
            $dateMap[] = date("Y-m-d",strtotime("+".$i." day"));
        }
        $res = [
            'code' => 1,
            'success' => true,
            'dateList' => $dateMap,
            'curDate' => $match_time,
            'matchState' => [
                0 => '未开赛',
                1 => '上半场',
                2 => '中场',
                3 => '下半场',
                4 => '加时',
                5 => '点球',
                '-1' => '完场',
                '-10' => '取消',
                '-11' => '待定',
                '-12' => '腰斩',
                '-13' => '中断',
                '-14' => '推迟',
            ],
            'leagueList' => []
        ];
        $fmatch = Fmatch::where('match_time', 'like', $match_time.'%');
        if ($league_id) {
            $fmatch = $fmatch->whereIn('league_id', explode(',', $league_id));
        }
        $match_map = $fmatch->orderBy('match_time', 'asc')->select('match_id','league_id','season_id','league_name','match_time','home_name','guest_name','home_id','guest_id','match_state','half_score','score','home_red','guest_red','home_yellow','guest_yellow','home_corner','guest_corner')->get()->toarray();
        $res['list'] = $match_map;
        if (count($match_map) > 0) {
            $leagueMap = Fmatch::where('match_time', 'like', $match_time.'%')->get(['league_name'])->toArray();
            $res['leagueList'] = array_unique(array_column($leagueMap, 'league_name'));
        }
        return $res;
    }




    //伤停
    public function missplayer(Request $request){

        $team_id = $request->input('team_id') ? $request->input('team_id') : false;
        $data = '';
        //查当前赛队的伤残
        if($team_id){
            $data = Fmissplayer::join('d_player','d_player.id', '=', 'player_id')->where('d_missplayer.team_id',$team_id)->select('d_missplayer.player_name','d_missplayer.player_id','d_missplayer.player_status_name','d_player.position','d_player.shirt_num')->get()->toarray();
        }
        return ['code' => 1,'success' => true,'list' => $data];
    }




    //阵容
   /* public function match_lineup(Request $request){
        //1：主队 2：客队
        $is_host = $request->input('is_host') ? $request->input('is_host') : 1;
        //比赛Id
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        //获取类型
        //1：首发 2：替补
        $subsitute= $request->input('subsitute') ? $request->input('subsitute') : 1;
        $data = '';
        if($match_id){
            $data = FmatchLineup::join('d_player','d_player.id', '=', 'player_id')->where('d_match_lineup.match_id',  $match_id)->where('d_match_lineup.subsitute',  $subsitute)->where('d_match_lineup.is_host',  $is_host)->select('d_match_lineup.player_name','d_match_lineup.player_number','d_player.logo','d_match_lineup.player_id')->get()->toarray();
        }
        
        return ['code' => 1,'success' => true,'list' => $data];
    }*/


    //比赛伤残 阵容合并
    public function match_lineup(Request $request){
        //比赛Id
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $data = array();
        
        //查当前赛队的伤残
        if($match_id){//主队伤残
            //获取主客队的ID
            $fmatch = Fmatch::where('match_id', $match_id)->select('home_id','guest_id')->get()->toarray();
            $id[] = $fmatch[0]['home_id'];
            $id[] = $fmatch[0]['guest_id'];
            $missplayer = Fmissplayer::join('d_player','d_player.id', '=', 'player_id')->whereIn('d_missplayer.team_id',$id)->select('d_missplayer.team_id','d_missplayer.player_name','d_missplayer.player_id','d_missplayer.player_status_name','d_player.position','d_player.shirt_num')->get()->toarray();
            foreach ($missplayer as $k => $v) {
                if($fmatch[0]['home_id']==$v['team_id']){//主队伤残
                    $data['home_missplayer'][]=$v;
                }else{
                    $data['guest_missplayer'][]=$v;
                }
            }

        //首发阵容
            $matchLineup = FmatchLineup::join('d_player','d_player.id', '=', 'player_id')->where('d_match_lineup.match_id',  $match_id)->select('d_match_lineup.subsitute','d_match_lineup.is_host','d_match_lineup.player_name','d_match_lineup.player_number','d_player.logo','d_match_lineup.player_id')->get()->toarray();
           // dd($matchLineup);
            foreach ($matchLineup as $k => $v) {
                //主队收发
                if($v['subsitute']==1&&$v['is_host']==1){
                    $data['home_Lineup'][]=$v;
                }elseif($v['subsitute']==1&&$v['is_host']==2){//客队首发
                    $data['guest_Lineup'][]=$v;
                }elseif($v['subsitute']==2&&$v['is_host']==1){//主队替补
                    $data['home_replace'][]=$v;
                }elseif($v['subsitute']==2&&$v['is_host']==2){//客队替补
                    $data['guest_replace'][]=$v;
                }
            }
        
        }

        return ['code' => 1,'success' => true,'list' => $data];
    }

  

    public function history_match(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $fmatch = Fmatch::where('match_id', $match_id)->select('home_id','guest_id')->get()->toarray();
        $data=[];
        $match = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['home_id'])->where('guest_id',$fmatch[0]['guest_id'])->orwhere('home_id',$fmatch[0]['guest_id'])->where('guest_id',$fmatch[0]['home_id'])->where('match_id','<>', $match_id)->select('league_name','match_time','home_id','guest_id','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
     
        $data = Fmatch::data_match($match,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);

        //主场的历史交锋、
        $match_home = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['home_id'])->where('guest_id',$fmatch[0]['guest_id'])->select('home_id','guest_id','league_name','match_time','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $datas = Fmatch::data_match($match_home,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);
        $row['all'] = $data;
        $row['all_home'] = $datas;
        return ['code' => 1,'success' => true,'list' => $row];
    }


    public function home_history_match(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $fmatch = Fmatch::where('match_id', $match_id)->select('home_id','guest_id')->get()->toarray();
        $data=[];
        //SELECT league_name,match_time,home_name,guest_name,score,home_id,guest_id FROM d_match WHERE home_id =13  AND match_id!=2467 and match_time < '2020-10-29' or  guest_id= 13  AND match_id!=2467   and match_time < '2020-10-29' ORDER BY match_time desc limit 10
   
        $match = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['home_id'])->where('score','<>','')->orwhere('score','<>','')->where('guest_id',$fmatch[0]['home_id'])->where('match_id','<>', $match_id)->select('league_name','match_time','home_id','guest_id','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();

        $data = Fmatch::data_match($match,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);

        //主场的历史交锋、
        $match_home = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['home_id'])->where('score','<>','')->select('league_name','match_time','home_id','guest_id','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $datas = Fmatch::data_match($match_home,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);



        $k_match = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['guest_id'])->where('score','<>','')->orwhere('score','<>','')->where('guest_id',$fmatch[0]['guest_id'])->where('match_id','<>', $match_id)->select('league_name','match_time','home_id','guest_id','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $data2 = Fmatch::data_match($k_match,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);

        //主场的历史交锋、
        $match_guest = Fmatch::where('match_id','<>', $match_id)->where('home_id',$fmatch[0]['guest_id'])->where('score','<>','')->select('league_name','match_time','home_id','guest_id','home_name','guest_name','score')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $datas2 = Fmatch::data_match($match_guest,$fmatch[0]['home_id'],$fmatch[0]['guest_id']);



        $row['home_all'] = $data;
        $row['match_home'] = $datas;
        $row['guest_all'] = $data2;
        $row['match_guest'] = $datas2;

        return ['code' => 1,'success' => true,'list' => $row];
    }


//未来赛事
   public function future_match(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $fmatch = Fmatch::where('match_id', $match_id)->select('home_id','guest_id','match_time')->get()->toarray();

        //SELECT league_name,match_time,home_name,guest_name,score,home_id,guest_id FROM d_match WHERE home_id =38549   and match_time > '2020-10-29 03:00:00' or  guest_id= 38549     and match_time > '2020-10-29 03:00:00' ORDER BY match_time desc limit 10
        $data['guest'] = Fmatch::where('match_time','>', $fmatch[0]['match_time'])->where('home_id',$fmatch[0]['guest_id'])->orwhere('guest_id',$fmatch[0]['guest_id'])->where('match_time','>', $fmatch[0]['match_time'])->select('league_name','match_time','home_id','guest_id','home_name','guest_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $data['home'] = Fmatch::where('match_time','>', $fmatch[0]['match_time'])->where('home_id',$fmatch[0]['home_id'])->orwhere('guest_id',$fmatch[0]['home_id'])->where('match_time','>', $fmatch[0]['match_time'])->select('league_name','match_time','home_id','guest_id','home_name','guest_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        return ['code' => 1,'success' => true,'list' => $data];
   }

    //比赛详情
    public function match_info(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $match = Fmatch::where('match_id', $match_id)->select('match_id','league_name','match_time','home_id','guest_id','home_name','guest_name','match_state','half_score','score','league_id','season_id')->get()->toarray();
        //获取球队头像
        $team_home = Fteam::where('team_id',$match[0]['home_id'])->select('logo_path')->get()->toarray();
        $team_guest = Fteam::where('team_id',$match[0]['guest_id'])->select('logo_path')->get()->toarray();
        $match[0]['home_logo'] = $team_home[0]['logo_path'];
        $match[0]['guest_logo'] = $team_guest[0]['logo_path'];
        return ['code' => 1,'success' => true,'List' => $match];
    }

}