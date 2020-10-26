<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fplayer extends Model
{
    protected $table = 'd_player';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $teamId = self::updateOrCreate($where, $data)->id;
        return $teamId;
    }
}