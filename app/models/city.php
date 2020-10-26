<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class city extends Model
{
    protected $table = 'd_city';

    protected $guarded = ['city_id'];
    protected $primaryKey = 'city_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->city_id;
        return $id;
    }
}