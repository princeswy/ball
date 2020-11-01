<?php

namespace App\Http\Controllers;

use App\models\Feventdetail;
use App\models\Fmstatis;
use Illuminate\Http\Request;
use App\models\FplayerCount;
use DB;
class FstatisController extends Controller
{
    
    //比赛事件
    public function index(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : false;
        $ret = [
            'code' => 1,
            'message' => '成功',
            'typeList' => Feventdetail::$event_type,
            'data' => [],
            'success' => true
        ];
        if (!$match_id) {
            $ret['code'] = 0;
            $ret['message'] = '参数有误，match_id为必传参数';
            $res['success'] = false;
            return $ret;
        }
        $statis = Fmstatis::where('match_id', $match_id)->first();
        if (!$statis) {
            $ret['code'] = 2;
            $ret['message'] = '暂无数据';
            return $ret;
        }
        $ret['data'] = $statis->toArray();
        return $ret;
    }



    


}
