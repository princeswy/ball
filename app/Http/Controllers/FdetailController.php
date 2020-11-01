<?php

namespace App\Http\Controllers;

use App\models\Feventdetail;
use Illuminate\Http\Request;
use DB;
class FdetailController extends Controller
{
    
    //比赛事件
    public function index(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : false;
        $ret = [
            'code' => 1,
            'message' => '成功',
            'typeList' => Feventdetail::$event_type,
            'list' => [],
            'success' => true
        ];
        if (!$match_id) {
            $ret['code'] = 0;
            $ret['message'] = '参数有误，match_id为必传参数';
            $res['success'] = false;
            return $ret;
        }
        $detail = Feventdetail::where('match_id', $match_id)->orderBy('happen_time', 'desc')->get();
        if (!$detail) {
            $ret['code'] = 2;
            $ret['message'] = '暂无数据';
            return $ret;
        }
        $detail_data = $detail->toArray();
        foreach ($detail_data as $k => $v) {
            $ret['list'][] = [
                'match_id' => $match_id,
                'happen_time' => $v['happen_time'],
                'type' => $v['type'],
                'type_name' => Feventdetail::$event_type[$v['type']],
                'is_host' => $v['is_host'],
                'player_name' => $v['player_name']
            ];
        }
        return $ret;
    }



    


}
