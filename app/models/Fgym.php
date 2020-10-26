<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fgym extends Model
{
    protected $table = 'd_gym';

    protected $guarded = ['gym_id'];
    protected $primaryKey = 'gym_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->gym_id;
        return $id;
    }
}