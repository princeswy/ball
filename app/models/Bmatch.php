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

class Bmatch extends Model
{
    use SoftDeletes;
    //
    protected $table = 'd_bmatch';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';

//    protected $dates = ['deleted_at'];

    public static function convert_qtMatch($data) {
        if (!isset($data->matchList)) {
            return false;
        }
        $kind_map = [
            1 => '常规',
            2 => '季后',
            3 => '季前',
            '-1' => '无分类',
        ];
        $ret = [];
        $team_id_map = Bteam::get(['id', 'out_team_id'])->toArray();
        $teams = array_column($team_id_map,'id','out_team_id');
        foreach ($data->matchList as $key => $val) {
            var_dump($key);
            $league_id = $val->leagueId;
            $season_name = strlen($val->season) == 2 ? $val->season + 2000 : (2000 + substr($val->season, 0,2)).'-'.(substr($val->season,-2) + 2000);
            $season_id = Bseason::firstOrCreate(['league_id' => $league_id, 'season_name' => $season_name])->id;
            $section_name = $val->group ? $val->roundTypeChs : $kind_map[$val->matchKind];
            $group_name = $val->group ? $val->group : ($val->roundTypeChs ? $val->roundTypeChs : $kind_map[$val->matchKind]);
            $section_id = Bsection::firstOrCreate(['season_id' => $season_id, 'league_id' => $league_id, 'section_name' => $section_name])->id;
            $group_id = Bgroup::firstOrCreate(['section_id' => $section_id, 'group_name' => $group_name])->id;
            $ret[] = [
                'home_id' => $teams[$val->homeTeamId],
                'away_id' => $teams[$val->awayTeamId],
                'out_match_id' => $val->matchId,
                'out_home_id' => $val->homeTeamId,
                'out_away_id' => $val->awayTeamId,
                'league_id' => $val->leagueId,
                'league_name' => $val->leagueChs,
                'home_name' => $val->homeTeamChs,
                'away_name' => $val->awayTeamChs,
                'match_time' => $val->matchTime,
                'group_id' => $group_id,
                'season_id' => $season_id,
                'state' => $val->matchState,
                'home_order' => $val->homeRankCn,
                'away_order' => $val->awayRankCn,
                'score' => $val->homeScore.'-'.$val->awayScore != '-' ? $val->homeScore.'-'.$val->awayScore : '',
                'first_score' => $val->home1.'-'.$val->away1 != '-' ? $val->home1.'-'.$val->away1 : '',
                'second_score' => $val->home2.'-'.$val->away2 != '-' ? $val->home2.'-'.$val->away2 : '',
                'third_score' => $val->home3.'-'.$val->away3 != '-' ? $val->home3.'-'.$val->away3 : '',
                'fourth_score' => $val->home4.'-'.$val->away4 != '-' ? $val->home4.'-'.$val->away4 : '',
                'overtimes' => $val->overtimeCount,
                'firstot' => $val->homeOT1.'-'.$val->awayOT1 != '-' ? $val->homeOT1.'-'.$val->awayOT1 : '',
                'secondot' => $val->homeOT2.'-'.$val->awayOT2 != '-' ? $val->homeOT2.'-'.$val->awayOT2 : '',
                'thirdot' => $val->homeOT3.'-'.$val->awayOT3 != '-' ? $val->homeOT3.'-'.$val->awayOT3 : '',
                'zl' => $val->isNeutral ? 1 : 0,
                'source' => 'win007'
            ];
        }
        return $ret;
    }
}