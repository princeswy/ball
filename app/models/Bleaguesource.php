<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Bleaguesource extends Model
{
    protected $table = 'd_bleague_source';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    public $timestamps = false;


    public static function boot()
    {
        parent::boot();

        static::creating(function ($data) {
            //
            self::league_init($data);
        });

        static::updating(function($data){

            self::league_init($data);

        });

    }


    public static function league_init($data) {

        #查询是否已匹配
        $league_map_mes = Bleaguemap::where( ['out_leagueid' => $data['league_id'], 'source' => $data['source']] )->first();

        if ( $league_map_mes ) {
            return true;
        }

        #通过联赛名查询内部联赛
        $league_mes = Bleague::where( 'league_name', $data['league_name'] )->first();

        if ( !$league_mes ) {
            return true;
        }

        $league_id = $league_mes['id'];

        $league_map_where = [
            'out_leagueid' => $data['league_id'],
            'source' => $data['source'],
        ];

        $in_league_map = [
            'league_id' => $league_id,
            'out_leagueid' => $data['league_id'],
            'league_name' => $data['league_name'],
            'source' => $data['source'],
        ];

        Bleaguemap::firstOrCreate($in_league_map);

        return true;
    }
}