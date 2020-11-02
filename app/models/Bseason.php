<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Bseason extends Model
{
    protected $table = 'd_bseason';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';

//     public $timestamps = false;
    protected $dates = ['deleted_at'];


    public static function insert_seasons($data)
    {
        if(!is_array($data) || empty($data)){
            return false;
        }
//        $data = arrays_unique($data);
//        $data = self::filter_season($data);
        foreach ($data as $key => $val) {
            self::updateOrCreate(['league_id' => $val['league_id']], $val);
        }
        /*var_dump($data);
        if(empty($data)){
            return false;
        }
        $season_chunk = array_chunk($data, 400);

        foreach ($season_chunk as $key=>$val)
        {
            $ret[] = self::create($val);
        }
        return $ret;*/
    }


    public static function filter_season($data)
    {
        $seasons = self::all(array('season_name','league_id','season_name_hk'))->toArray();
        if(empty($seasons)){
            return $data;
        }

        $diff_data = array_intersect_deep($data, $seasons);
        return $diff_data;
    }
}