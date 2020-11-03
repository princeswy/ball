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

    //计算比赛数据
    public static function data_match($data,$home_id,$guest_id){
        $goal_num=0;
        $num=0;
        $win=0;$draw=0;$lost=0;
        $is_win='';

        foreach ($data as $k => $v) {
            $score = explode('-',$v['score']);
            // var_dump($score);
            // $data['match'][$k]['handicap']='-';
            //进球数
            if($home_id==$v['home_id']){
                $goal_num+=$score[0];
                if($score[0]>$score[1]){
                    $is_win = '赢';
                    $win+=1;
                }elseif($score[0]<$score[1]){
                    $is_win = '输';
                    $lost+=1;
                }else{
                    $is_win = '平';
                    $draw+=1;
                }
            }else{

                $goal_num+=$score[1];
                if($score[0]<$score[1]){
                    $is_win = '赢';
                    $win+=1;
                }elseif($score[0]>$score[1]){
                    $is_win = '输';
                    $lost+=1;
                }else{
                    $is_win = '平';
                    $draw+=1;
                }
            }
            $num += $score[0]+$score[1];
            //计算输赢
            $data[$k]['is_win']=$is_win;

        }
        $lost_num=$num-$goal_num;
        //赢盘率
        $datas['data']['ratio']=intval(($win/count($data))*100).'%';
        //进球数
        $datas['data']['goal_num']=$goal_num;
        //失球数
        $datas['data']['lost_num']=$num-$goal_num;
        $datas['data']['win']=$win;
        $datas['data']['draw']=$draw;
        $datas['data']['lost']=$lost;
        $datas['match']=$data;
        return $datas;
    }
}