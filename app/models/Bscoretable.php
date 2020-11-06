<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 15:51
 */

namespace App\models;


use Illuminate\Database\Eloquent\Model;

class Bscoretable extends Model
{
    //
    protected $table = 'd_bscoretable';

    protected $guarded = ['id'];
    protected $primaryKey = 'id';

    public static function convert_qt($out_data, $league_id) {
        echo "<pre>";
        if ( !$out_data || !is_array($out_data) || !$league_id ) {
            return false;
        }

        $out_teamid = array_column($out_data, 'teamId');

        $team_map = Bteam::whereIn('out_team_id', $out_teamid)->get(['out_team_id', 'id', 'short'])->toArray();

        #获取赛事类型
        $league_mes = Bleague::where( 'id', $league_id )->first();
        $league_type = $league_mes->sclass_kind;

        #联赛
        if ( $league_type == 1 ) {
            $ret = self::handle_scoretable_league($out_data, $league_id, $league_type,  $team_map);
        }
        #杯赛
        else if ( $league_type == 2 ) {
            $ret = self::handle_scoretable_cup($out_data, $league_id, $league_type, $team_map);
        }

        return $ret;
    }

    public static function handle_scoretable_league($out_data, $league_id, $league_type, $team_map) {

        $ret = [];
        $source = 'win007';

        foreach ( $out_data as $key => $val ) {

            $team_ids = array_column($team_map, 'id', 'out_team_id');

            $team_name_map = array_column($team_map, 'short', 'out_team_id');

            $team_id = $team_ids[$val->teamId];

            $team_name = $team_name_map[$val->teamId];

            if ( !$team_id ) {
                continue;
            }

            $season_name = $val->season;
            $season_map = [];
            if ( strstr($season_name, '-') ) {
                $season_name_map = explode( '-', $season_name );
                $season_map = [];

                foreach ( $season_name_map as $s_key => $s_val ) {

                    if ( substr( $s_val, 2 ) != 20 && strlen( $s_val ) < 4 ) {
                        $season_map[] = '20'.$s_val;
                    }
                    else{
                        $season_map[]= $s_val;
                    }

                }
            }
            else{
                if ( substr( $season_name, 2 ) != 20 && strlen( $season_name ) < 4 ) {
                    $season_map[] = '20'.$season_name;
                }
                else{
                    $season_map = $season_name;
                }
            }
            $season_name = implode('-', $season_map);

            $season_id = Bseason::firstOrCreate( [ 'league_id' => $league_id, 'season_name' => $season_name ] )->id;

//            $ret[$key]['source'] = $source;
            $ret[$key]['league_type'] = $league_type;
            $ret[$key]['league_id'] = $league_id;
            $ret[$key]['team_id'] = $team_id;
            $ret[$key]['season_id'] = $season_id;
            $ret[$key]['season_name'] = $season_name;
            $ret[$key]['out_team_id'] = $val->teamId;
            $ret[$key]['team_name'] = $team_name ? $team_name : $val->nameChs;
            $ret[$key]['home_win'] = $val->homeWin;
            $ret[$key]['home_loss'] = $val->homeLose;
            $ret[$key]['away_win'] = $val->awayWin;
            $ret[$key]['away_loss'] = $val->awayLose;
            $ret[$key]['win_scale'] = $val->winRate;
            $ret[$key]['state'] = $val->state;
            $ret[$key]['home_order'] = $val->homeRank;
            $ret[$key]['away_order'] = $val->awayRank;
            $ret[$key]['rank'] = $val->totalRank;
            $ret[$key]['home_score'] = $val->homeScore;
            $ret[$key]['home_lossscore'] = $val->homeLossScore;
            $ret[$key]['away_score'] = $val->awayScore;
            $ret[$key]['away_lossscore'] = $val->awayLossScore;
            $ret[$key]['near_win'] = $val->recentTenWin;
            $ret[$key]['near_loss'] = $val->recentTenLose;
            $ret[$key]['type'] = $league_type;
        }

        return $ret;

    }

    public static function handle_scoretable_cup($out_data, $league_id, $league_type, $team_map) {

        $ret = [];
        $source = 'win007';

        $season_name = $out_data['Season'];

        if ( strstr($season_name, '-') ) {
            $season_name_map = explode( '-', $season_name );
            $season_map = [];

            foreach ( $season_name_map as $s_key => $s_val ) {

                if ( substr( $s_val, 2 ) != 20 && strlen( $s_val ) < 4 ) {
                    $season_map[] = '20'.$s_val;
                }
                else{
                    $season_map[]= $s_val;
                }

            }
        }
        else{
            if ( substr( $season_name, 2 ) != 20 && strlen( $season_name ) < 4 ) {
                $season_map[] = '20'.$season_name;
            }
            else{
                $season_map = $season_name;
            }
        }

        $season_id = Bseason::firstOrCreate( [ 'league_id' => $league_id, 'season_name' => $season_name ] )->id;

        $season_name = implode('-', $season_map);

        foreach ( $out_data->list as $key => $val ) {

            $team_ids = array_column($team_map, 'id', 'out_team_id');

            $team_name_map = array_column($team_map, 'short', 'out_team_id');

            $team_id = $team_ids[$val->teamId];

            $team_name = $team_name_map[$val->teamId];

            if ( !$team_id ) {
                continue;
            }

//            $ret[$key]['source'] = $source;
            $ret[$key]['league_type'] = $league_type;
            $ret[$key]['league_id'] = $league_id;
            $ret[$key]['team_id'] = $team_id;
            $ret[$key]['season_id'] = $season_id;
            $ret[$key]['season_name'] = $season_name;
            $ret[$key]['out_team_id'] = $val->teamId;
            $ret[$key]['team_name'] = $team_name ? $team_name : $val->nameChs;
            $ret[$key]['win'] = $val->winCount;
            $ret[$key]['lose'] = $val->loseCount;
            $ret[$key]['total_get'] = $val->totalScore;
            $ret[$key]['total_lose'] = $val->totalLoss;
            $ret[$key]['state'] = $val->state;
            $ret[$key]['rank'] = $val->rank;
            $ret[$key]['type'] = $league_type;
            $ret[$key]['group'] = $val->group;
        }

        return $ret;

    }
}