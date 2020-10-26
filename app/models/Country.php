<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'd_country';

    protected $guarded = ['country_id'];
    protected $primaryKey = 'country_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $id = self::updateOrCreate($where, $data)->country_id;
        return $id;
    }
}