<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fformation extends Model
{
    protected $table = 'd_match_formation';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->id;
        return $id;
    }
}