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

    public function show(Request $request) {
        $match_map = Fmatch::whereBetween('match_time', ['2020-10-26 00:00:00', '2020-10-26 23:59:59'])->get()->toArray();
        return $match_map;
    }
}