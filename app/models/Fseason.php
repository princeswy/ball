<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fseason extends Model
{
    protected $table = 'd_season';

    protected $guarded = ['season_id'];
    protected $primaryKey = 'season_id';
//    public $timestamps = false;

    public static function handleSeason ($data) {
        $where = [
            'league_id' => $data['league_id'],
            'season_name' => $data['season_name']
        ];
        $seasonId = self::updateOrCreate($where, $data)->season_id;
        return $seasonId;
    }
}