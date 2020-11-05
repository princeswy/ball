<?php
namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Bmatchlineup extends Model {
    protected $table = 'd_bmatch_lineup';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
}
