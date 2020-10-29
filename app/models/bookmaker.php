<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class bookmaker extends Model
{
    protected $table = 'd_bookmaker';

    protected $guarded = ['bookmaker_id'];
    protected $primaryKey = 'bookmaker_id';
//    public $timestamps = false;

    public static function handleData ($where, $data) {
        $id = self::updateOrCreate($where, $data)->bookmaker_id;
        return $id;
    }
}