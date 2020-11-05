<?php

namespace App\Http\Controllers;

use App\models\Bhcodds;
use App\models\Bodds;
use App\models\Btotalhandicap;
use Illuminate\Http\Request;
class BoddsController extends Controller
{
    
    public function odds_list(Request $request){
        $match_id = $request->input('match_id') ? $request->input('match_id') : '';

        $odds_type = $request->input('odds_type') ? $request->input('odds_type') : '3W';

        if ($odds_type === '3W') {
            $odds = Bodds::get_odds([$match_id]);
        }
        if ($odds_type === 'rf') {
            $odds = Bhcodds::get_odds([$match_id]);
        }
        if ($odds_type === 'dxf') {
            $odds = Btotalhandicap::get_odds([$match_id]);
        }

//        $odds = call_user_func_array(self::$odds_function[$odds_type], [[$match_id]]);

        if(!$match_id || !$odds[$match_id] ){
            $data['list'] = [];
            $data['code'] = 1;
            $data['msg'] = '暂无数据';
            $data['systime'] = date("Y-m-d H:i:s" ,time());
            return $data;
        }
        $data['code'] = 1;
        $data['msg'] = '获取成功';
        $data['systime'] = date("Y-m-d H:i:s" ,time());
        $data['list'] = null;

//        $final_odds = $base = [];
        if(is_array($odds)) {
            foreach ($odds as $key => $val) {
                $val = is_array($val) ? $val : json_decode($val, true);

                if($val) {

                    foreach ( $val as $v_key => $v_val ) {

                        $val[$v_key]['key'] = $v_val['bookmaker_id'];

                    }

                    $base = $val;
                }
            }
        }

        $data['list'] = array_values($base);

        return $data;
    }



    


}
