<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Bsection extends Model
{
    //
    protected $table = 'd_bsection';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';

}