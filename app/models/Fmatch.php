<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fmatch extends Model
{
    protected $table = 'd_match';

    protected $guarded = ['match_id'];
    protected $primaryKey = 'match_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->match_id;
        return $id;
    }
}