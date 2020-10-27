<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fshooters extends Model
{
    protected $table = 'd_shooters';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function handleData ($where, $data) {
        $id = self::updateOrCreate($where, $data)->id;
        return $id;
    }
}