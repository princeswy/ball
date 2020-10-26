<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fteam extends Model
{
    protected $table = 'd_team';

    protected $guarded = ['team_id'];
    protected $primaryKey = 'team_id';
//    public $timestamps = false;

    public static function handleSection ($where, $data) {
        $teamId = self::updateOrCreate($where, $data)->team_id;
        return $teamId;
    }
}