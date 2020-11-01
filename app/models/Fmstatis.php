<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Fmstatis extends Model
{
    protected $table = 'd_mstatis';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;


    public static function handleData ($out_data) {
        if (!$out_data) {
            return false;
        }
        $statis_data = [];
        foreach ($out_data->technic as $key => $val) {
            $out_match_id = $val->matchId;
            $match_data = Fmatch::where('out_match_id', $out_match_id)->first();
            $match_id = $match_data ? $match_data->toArray()['match_id'] : 0;
            $content = $val->technicCount;
            $contentData = explode(';', $content);
            $data = [
                'match_id' => $match_id,
                'out_match_id' => $out_match_id
            ];
            foreach ($contentData as $k => $v) {
                list($type, $home, $guest) = explode(',', $v);
                // 射门
                if ($type == 3) {
                    $data['home_smcs'] = $home;
                    $data['guest_smcs'] = $guest;
                }
                // 射正
                if ($type == 4) {
                    $data['home_szqmcs'] = $home;
                    $data['guest_szqmcs'] = $guest;
                }
                // 犯规
                if ($type == 5) {
                    $data['home_fgcs'] = $home;
                    $data['guest_fgcs'] = $guest;
                }
                // 角球
                if ($type == 6) {
                    $data['home_jqcs'] = $home;
                    $data['guest_jqcs'] = $guest;
                }
                // 越位
                if ($type == 9) {
                    $data['home_ywcs'] = $home;
                    $data['geust_ywcs'] = $guest;
                }
                // 黄牌
                if ($type == 11) {
                    $data['home_hups'] = $home;
                    $data['guest_hups'] = $guest;
                }
                // 控球率
                if ($type == 14) {
                    $data['home_kql'] = doubleval($home);
                    $data['guest_kql'] = doubleval($guest);
                }
                // 救球
                if ($type == 16) {
                    $data['home_jq'] = $home;
                    $data['guest_jq'] = $guest;
                }
                // 界外球
                if ($type == 40) {
                    $data['home_jwq'] = $home;
                    $data['guest_jwq'] = $guest;
                }
                // 门球
                if ($type == 37) {
                    $data['home_mq'] = $home;
                    $data['guest_mq'] = $guest;
                }
                // 任意球
                if ($type == 8) {
                    $data['home_ryq'] = $home;
                    $data['guest_ryq'] = $guest;
                }
                // 换人
                if ($type == 29) {
                    $data['home_hrs'] = $home;
                    $data['guest_hrs'] = $guest;
                }
                // 危险进攻
                if ($type == 44) {
                    $data['home_wxjg'] = $home;
                    $data['guest_wxjg'] = $guest;
                }
                // 射门不中
                if ($type == 34) {
                    $data['home_smbz'] = $home;
                    $data['guest_smbz'] = $guest;
                }
            }
            $statis_data[] = $data;
        }
        return $statis_data;
    }
}