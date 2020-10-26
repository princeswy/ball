<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Continent extends Model
{
    protected $table = 'd_continent';

    protected $guarded = ['continent_id'];
    protected $primaryKey = 'continent_id';
}