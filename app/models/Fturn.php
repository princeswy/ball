<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fturn extends Model
{
    protected $table = 'd_turn';

    protected $guarded = ['trun_id'];
    protected $primaryKey = 'trun_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->trun_id;
        return $id;
    }
}