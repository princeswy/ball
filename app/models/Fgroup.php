<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fgroup extends Model
{
    protected $table = 'd_group';

    protected $guarded = ['group_id'];
    protected $primaryKey = 'group_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->group_id;
        return $id;
    }
}