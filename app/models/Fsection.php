<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fsection extends Model
{
    protected $table = 'd_section';

    protected $guarded = ['section_id'];
    protected $primaryKey = 'section_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $sectionId = self::updateOrCreate($where, $data)->section_id;
        return $sectionId;
    }
}