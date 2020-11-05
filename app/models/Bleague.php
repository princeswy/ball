<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Bleague extends Model
{
    protected $table = 'd_bleague';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';
//    public $timestamps = false;

    public static function convert_qtLeague($data, &$seasons)
    {
        $data = $data->list;
        if(!is_array($data) || empty($data)){
            return false;
        }
        foreach ($data as $key=>$val)
        {
            $leagues[$key]['out_league_id'] = $val->leagueId;
            $leagues[$key]['league_name'] = $val->nameChsShort;
            $leagues[$key]['full_name'] = $val->nameChs;
            $leagues[$key]['full_name_hk'] = $val->nameCht;

            $leagues[$key]['full_name_en'] = $val->nameEn;
            $leagues[$key]['type'] = $val->leagueType;
            $leagues[$key]['league_color'] = $val->color;
            $leagues[$key]['country_id'] = $val->countryId;
            $leagues[$key]['country'] = isset($val->countryCn) ? $val->countryCn : '';

            $leagues[$key]['sclass_kind'] = $val->leagueKind;
//            $leagues[$key]['sclass_time'] = $val->partTime;
            $leagues[$key]['curr_season'] = $val->currentSeason;


            $year = strlen($val->currentSeason) == 2 ? $val->currentSeason + 2000 : (2000 + substr($val->currentSeason, 0,2)).'-'.(substr($val->currentSeason,-2) + 2000);
            $seasons[$key]['league_id'] = intval($val->leagueId);
            $seasons[$key]['season_name'] = (string) $year;
            $seasons[$key]['season_name_hk'] = (string) $year;
            $seasons[$key]['logo_path'] = isset($val->logo) ? ($val->logo ? $val->logo.'?win007=sell' : '') : '';

        }

        return $leagues;
    }

    public static function save_league($leaguedata, $mapdata)
    {
        $leaguemap = Bleaguemap::where(['out_leagueid' => $mapdata['out_leagueid'], 'source' => $mapdata['source']])->first();

        if ($leaguemap) {
            $league_id = $leaguemap->league_id;
            $league = Bleague::find($league_id);

            return $league;

        } else {
            try {
                # 新增联赛
                DB::beginTransaction();

                $newleague = self::create($leaguedata);
                $mapdata['league_id'] = $newleague->id;

                $map = Bleaguemap::updateOrCreate(['out_leagueid' => $mapdata['out_leagueid'], 'source' => $mapdata['source']], $mapdata);

                if ($map->id) {
                    DB::commit();
                    return $newleague;
                } else {
                    DB::rollBack();
                }

            } catch (\Exception $e) {
//                Log::info('Exception', [$e->getMessage()]);
                var_dump($e->getMessage());
                DB::rollBack();
            }
            return false;
        }
    }
}