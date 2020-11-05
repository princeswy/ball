<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-27
 * Time: 11:10
 */

namespace App\Http\Controllers;


use App\models\Fevent;
use App\models\Fmatch;
use App\models\FmatchLineup;
use App\models\Fmissplayer;
use App\models\Fteam;
use Illuminate\Http\Request;

use App\models\Bmatch;
use App\models\Bteam;

use DB;
class FmatchController extends Controller
{

//
    public function show(Request $request) {
        $date = date('Y-m-d');
        $match_time = $request->input('match_time') ? $request->input('match_time') : $date;
        $league_id = $request->input('league_id') ? $request->input('league_id') : false;
        $match_state = $request->input('match_state') ? $request->input('match_state') : 0;
        $match_type = $request->input('match_type') ? $request->input('match_type') : 1; // 1是足球 2是篮球
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
            'sysTime' => date('Y-m-d H:i:s'),
            'matchState' => [],
            'leagueList' => [],
            'list' => []
        ];
        if ($match_type == 1) {
            $fmatch = Fmatch::where('match_time', 'like', $match_time . '%');
            if ($league_id) {
                $fmatch = $fmatch->whereIn('league_id', explode(',', $league_id));
            }
            // 进行中
            if ($match_state == 1) {
                $fmatch = $fmatch->whereIn('match_state', [1, 2, 3, 4, 5]);
            }
            // 未开赛
            if ($match_state == 2) {
                $fmatch = $fmatch->where('match_state', 0);
            }
            if ($match_state == 3) {
                $fmatch = $fmatch->where('match_state', '-1');
            }
            $match_map = $fmatch->orderBy('match_time', 'asc')->select(DB::raw("$match_type as match_type"),'match_id', 'out_match_id', 'league_id', 'season_id', 'league_name', 'match_time', 'home_name', 'guest_name', 'home_id', 'guest_id', 'match_state', 'half_score', 'score', 'home_red', 'guest_red', 'home_yellow', 'guest_yellow', 'home_corner', 'guest_corner', 'home_rank', 'guest_rank')->get();
            if (!$match_map) {
                $res['list'] = [];
                return $res;
            }
            $match_map = $match_map->toarray();
            $home_id_map = array_unique(array_column($match_map, 'home_id'));
            $guest_id_map = array_unique(array_column($match_map, 'guest_id'));
            $team_id_map = array_unique(array_merge($home_id_map, $guest_id_map));
            $team_data = Fteam::whereIn('team_id', $team_id_map)->get(['team_id', 'logo_path'])->toArray();
            $team_map = array_column($team_data, 'logo_path', 'team_id');
            foreach ($match_map as $k => $v) {
                $match_map[$k]['home_logo'] = isset($team_map[$v['home_id']]) ? $team_map[$v['home_id']] : '';
                $match_map[$k]['guest_logo'] = isset($team_map[$v['guest_id']]) ? $team_map[$v['guest_id']] : '';
                $event_data = Fevent::where('match_id', $v['match_id'])->first();
                if (!$event_data) {
                    $match_map[$k]['start_time'] = $v['match_time'];
                } else {
                    $match_map[$k]['start_time'] = $event_data->toArray()['start_time'];
                }
            }
            $res['list'] = $match_map;
            if (count($match_map) > 0) {
                $leagueMap = Fmatch::where('match_time', 'like', $match_time . '%')->get(['league_name', 'league_id'])->toArray();
                $res['leagueList'] = array_unique(array_column($leagueMap, 'league_name', 'league_id'));
            }
            $res['matchState'] = [0 => '未开赛',1 => '上半场',2 => '中场',3 => '下半场',4 => '加时',5 => '点球','-1' => '完场','-10' => '取消','-11' => '待定','-12' => '腰斩','-13' => '中断','-14' => '推迟'];
        }else{//竞彩篮球

            $Bmatch = Bmatch::where('match_time', 'like', $match_time . '%');
            if ($league_id) {
                $Bmatch = $Bmatch->whereIn('league_id', explode(',', $league_id));
            }
            // 进行中
            if ($match_state == 1) {
                $Bmatch = $Bmatch->whereIn('state', [1, 2, 3, 4, 5, 6, 7]);
            }
            // 未开赛
            if ($match_state == 2) {
                $Bmatch = $Bmatch->where('state', 0);
            }
            //完场
            if ($match_state == 3) {
                $Bmatch = $Bmatch->where('state', '-1');
            }
            $Bmatch_map = $Bmatch->orderBy('match_time', 'asc')->select(DB::raw("$match_type as match_type"),'id as match_id','out_match_id','league_id','season_id','league_name','match_time','home_name','away_name','home_id','away_id','state as match_state','score','first_score','second_score','third_score','fourth_score','overtimes','firstot','secondot','thirdot','remain_time')->get();
            if (!$Bmatch_map) {
                $res['list'] = [];
                return $res;
            }
            $Bmatch_map = $Bmatch_map->toarray();
            $res['matchState'] = [0 => '未开赛',1 => '一节',2 => '二节',3 => '三节',4 => '四节',5 => "1'OT",6 => "2'OT",7 => "3'OT",'50' => '中场','-1' => '完场','-2' => '待定','-3' => '中断','-4' => '取消','-5' => '推迟'];
            $res['list'] = $Bmatch_map;
            if (count($Bmatch_map) > 0) {
                $BleagueMap = Bmatch::where('match_time', 'like', $match_time . '%')->get(['league_name', 'league_id'])->toArray();
                $res['leagueList'] = array_unique(array_column($BleagueMap, 'league_name', 'league_id'));
            }
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
            $matchLineup = FmatchLineup::join('d_player','d_player.id', '=', 'player_id')->where('d_match_lineup.match_id',  $match_id)->select('d_match_lineup.subsitute','d_match_lineup.is_host','d_match_lineup.player_name','d_match_lineup.player_number','d_player.logo','d_match_lineup.player_id', 'd_match_lineup.pos')->get()->toarray();
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
        if($match_type==2){
        $data['guest'] = Fmatch::where('match_time','>', $fmatch[0]['match_time'])->where('home_id',$fmatch[0]['guest_id'])->orwhere('guest_id',$fmatch[0]['guest_id'])->where('match_time','>', $fmatch[0]['match_time'])->select('league_name','match_time','home_id','guest_id','home_name','guest_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        $data['home'] = Fmatch::where('match_time','>', $fmatch[0]['match_time'])->where('home_id',$fmatch[0]['home_id'])->orwhere('guest_id',$fmatch[0]['home_id'])->where('match_time','>', $fmatch[0]['match_time'])->select('league_name','match_time','home_id','guest_id','home_name','guest_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();
        }
        if($match_type==2){
            $Bmatch = Bmatch::where('id', $match_id)->select('home_id','away_id','match_time')->get()->toarray();

            $data['guest'] = Bmatch::where('match_time','>', $Bmatch[0]['match_time'])->where('home_id',$Bmatch[0]['away_id'])->orwhere('away_id',$Bmatch[0]['away_id'])->where('match_time','>', $Bmatch[0]['match_time'])->select('league_name','match_time','home_id','away_id','home_name','away_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();


            $data['home'] = Bmatch::where('match_time','>', $Bmatch[0]['match_time'])->where('home_id',$Bmatch[0]['home_id'])->orwhere('away_id',$Bmatch[0]['home_id'])->where('match_time','>', $Bmatch[0]['match_time'])->select('league_name','match_time','home_id','away_id','home_name','away_name')->skip(0)->take(10)->orderBy('match_time', 'desc')->get()->toarray();

        }

        return ['code' => 1,'success' => true,'list' => $data];
    }

    //比赛详情
    public function match_info(Request $request){
        $match_type = $request->input('match_type') ? $request->input('match_type') : 1; //比赛类型 1：足球 2：篮球
        $match_id = $request->input('match_id') ? $request->input('match_id') : 0;
        $out_match_id = $request->input('out_match_id') ? $request->input('out_match_id') : 0;
        if($match_type==1){
            if ($match_id) {
                $match = Fmatch::where('match_id', $match_id)->select('match_id', 'out_match_id', 'league_name', 'match_time', 'home_id', 'guest_id', 'home_name', 'guest_name', 'match_state', 'half_score', 'score', 'league_id', 'season_id')->get()->toarray();
            } else if ($out_match_id) {
                $match = Fmatch::where('out_match_id', $out_match_id)->select('match_id', 'out_match_id', 'league_name', 'match_time', 'home_id', 'guest_id', 'home_name', 'guest_name', 'match_state', 'half_score', 'score', 'league_id', 'season_id')->get()->toarray();
            } else {
                return ['code' => 0,'success' => false,'list' => [], 'message' => '参数无效', 'sysTime' => date('Y-m-d H:i:s')];
            }
            //获取球队头像
            $team_home = Fteam::where('team_id',$match[0]['home_id'])->select('logo_path')->get()->toarray();
            $team_guest = Fteam::where('team_id',$match[0]['guest_id'])->select('logo_path')->get()->toarray();
            $match[0]['home_logo'] = $team_home[0]['logo_path'];
            $match[0]['guest_logo'] = $team_guest[0]['logo_path'];
            $event_data = Fevent::where('match_id', $match[0]['match_id'])->first();
            if (!$event_data) {
                $match[0]['start_time'] = $match[0]['match_time'];
            } else {
                $match[0]['start_time'] = $event_data->toArray()['start_time'];
            }
            $match[0]['match_type'] = $match_type;
        }else{
            //竞彩篮球的
            if ($match_id) {
                $match = Bmatch::where('id', $match_id)->select('id as match_id','out_match_id','league_id','season_id','league_name','match_time','home_name','away_name','home_id','away_id','state as match_state','score','first_score','second_score','third_score','fourth_score','overtimes','firstot','secondot','thirdot','remain_time')->get()->toarray();
            } else if ($out_match_id) {
                $match = Bmatch::where('out_match_id', $out_match_id)->select('id as match_id','out_match_id','league_id','season_id','league_name','match_time','home_name','away_name','home_id','away_id','state as match_state','score','first_score','second_score','third_score','fourth_score','overtimes','firstot','secondot','thirdot','remain_time')->get()->toarray();
            } else {
                return ['code' => 0,'success' => false,'list' => [], 'message' => '参数无效', 'sysTime' => date('Y-m-d H:i:s')];
            }
            //获取球队头像
            $team_home = Bteam::where('id',$match[0]['home_id'])->select('team_logo')->get()->toarray();
            $team_away = Bteam::where('id',$match[0]['away_id'])->select('team_logo')->get()->toarray();
            $match[0]['home_logo'] = $team_home[0]['team_logo'];
            $match[0]['away_logo'] = $team_away[0]['team_logo'];
            //$event_data = Fevent::where('match_id', $match[0]['match_id'])->first();
            //if (!$event_data) {
            $match[0]['start_time'] = $match[0]['match_time'];
            $match[0]['match_type'] = $match_type;
            //} else {
            // $match[0]['start_time'] = $event_data->toArray()['start_time'];
            //   }

        }
        return ['code' => 1,'success' => true,'list' => $match, 'sysTime' => date('Y-m-d H:i:s')];

    }

    // 推荐比赛
    public function recommend_list(Request $request) {
        $match_type = $request->input('match_type') ? $request->input('match_type') : 1; //比赛类型 1：足球 2：篮球
        $ret = [
            'code' => 1,
            'success' => true,
            'message' => '成功',
            'sysTime' => date('Y-m-d H:i:s'),
            'list' => []
        ];
        if ($match_type == 1) {
            $match_map = [];
            for ($i = 2; $i <= 24; $i ++) {
                $time = date('Y-m-d H:i:s', strtotime('+'.$i.' hours'));
                $match_map = Fmatch::where('match_time', '>=', date('Y-m-d H:i:s'))->where('match_time', '<=', $time)->whereIn('match_state', [0, 1, 2, 3, 4, 5])->orderBy('match_time', 'asc')->limit(30)->get();
                if ($match_map) {
                    $match_map = $match_map->toArray();
                    break;
                }
            }
            if ($match_map) {
                $home_id_map = array_unique(array_column($match_map, 'home_id'));
                $guest_id_map = array_unique(array_column($match_map, 'guest_id'));
                $team_id_map = array_unique(array_merge($home_id_map, $guest_id_map));
                $team_data = Fteam::whereIn('team_id', $team_id_map)->get(['team_id', 'logo_path'])->toArray();
                $team_map = array_column($team_data, 'logo_path', 'team_id');
                foreach ($match_map as $key => $val) {
                    $start_time = $val['match_time'];
                    $event_data = Fevent::where('match_id', $val['match_id'])->first();
                    if ($event_data) {
                        $start_time = $event_data->toArray()['start_time'];
                    }
                    $ret['list'][] = [
                        'match_id' => $val['match_id'],
                        'out_match_id' => $val['out_match_id'],
                        'home_name' => $val['home_name'],
                        'guest_name' => $val['guest_name'],
                        'match_state' => $val['match_state'],
                        'half_score' => $val['half_score'],
                        'score' => $val['score'],
                        'league_name' => $val['league_name'],
                        'match_time' => $val['match_time'],
                        'start_time' => $start_time,
                        'home_logo' => $team_map[$val['home_id']],
                        'guest_logo' => $team_map[$val['guest_id']],
                    ];
                }
            }
            return $ret;
        }
    }

}