<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bteam extends Model
{
    use SoftDeletes;
    protected $table = 'd_bteam';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
}