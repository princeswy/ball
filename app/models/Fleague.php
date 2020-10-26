<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fleague extends Model
{
    protected $table = 'd_league';

    protected $guarded = ['league_id'];
    protected $primaryKey = 'league_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->league_id;
        return $id;
    }
}