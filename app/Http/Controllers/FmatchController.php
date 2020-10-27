<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-27
 * Time: 11:10
 */

namespace App\Http\Controllers;


use App\models\Fmatch;
use Illuminate\Http\Request;

class FmatchController extends Controller
{

//
    public function show(Request $request) {
    	$where = request()->all();
        //$match_map = Fmatch::whereBetween('match_time', ['2020-10-26 00:00:00', '2020-10-26 23:59:59'])->get()->toArray();
        $match_time = $where['match_time'];
        $match_map = Fmatch::where('match_time', 'like', $match_time.'%')->orderBy('match_time', 'asc')->select('match_id','league_name','match_time','home_name','guest_name','match_state','half_score','score','home_red','guest_red','home_yellow','guest_yellow','home_corner','guest_corner')->get()->toarray();
        return json_encode($match_map);
    }
}