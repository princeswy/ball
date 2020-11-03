<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Bgroup extends Model
{
    //
    protected $table = 'd_bgroup';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';

}