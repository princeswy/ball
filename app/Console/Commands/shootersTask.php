<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Continent;
use App\models\Country;
use App\models\Fgym;
use App\models\Fleague;
use App\models\Fmanager;
use App\models\Fplayer;
use App\models\Freferee;
use App\models\Fseason;
use App\models\Fsection;
use App\models\Fshooters;
use App\models\Fteam;
use App\models\Task;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use MongoDB\Driver\Manager;

class ShootersTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:shootersTask {--league_id=} {--season=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探球员技术统计数据';
    public static $Url = 'http://interface.win007.com/football/topScorer.aspx';

    public function handle () {
        $league_id = $this->option('league_id');
        $season = $this->option('season');
        if ($league_id) {
            $league = Fleague::where('league_id', $league_id)->first();
            if (!$league) {
                $this->error('传入的联赛ID有误，请确认无误后再执行');
                exit;
            }
            $leagueMap = [$league->toArray()];
            if ($season) {
                $leagueMap['cur_season'] = $season;
            }
        } else {
            $leagueMap = Fleague::get()->toArray();
        }
        $this->info('共'.count($leagueMap).'个联赛的数据。');
        foreach ($leagueMap as $league_key => $league_val) {
            $this->info('正在处理第'.($league_key + 1).'个赛事-------'.$league_val['league_name']);
            $url = self::$Url.'?leagueId='.$league_val['out_league_id'];
            $this->handleData($url, $league_val);
            if (count($leagueMap) === ($league_key + 1)) {
                continue;
            }
            $this->info('第'.count($leagueMap).'--->'.($league_key + 1).' 个赛事处理完毕，程序暂停15秒');
            sleep(15);
        }
        $this->info('处理结束，共'.count($leagueMap).'个联赛的数据。');
    }

    public function handleData($url, $leagueData) {
        $res = self::send_request($url);
        $resData = json_decode($res['content']);
        if (!$resData->list || count($resData->list) === 0) {
            $this->info($leagueData['league_name'].'---赛事暂无数据');
            return false;
            exit;
        }
        foreach ($resData->list as $key => $val) {
            // 赛季
            $seasonData = [
                'league_id' => $leagueData['league_id'],
                'out_league_id' => $leagueData['out_league_id'],
                'season_name' => $leagueData['cur_season'],
            ];
            $seasonId = Fseason::handleSeason($seasonData);
            // 球队
            $teamWhere = [
                'out_teamid' => $val->teamID,
                'team_name' => $val->teamNameChs,
            ];
            $teamData = [
                'out_teamid' => $val->teamID,
                'team_name' => $val->teamNameChs,
                'team_name_hk' => $val->teamNameCht,
                'team_name_en' => $val->teamNameEn,
            ];
            $teamId = Fteam::handleSection($teamWhere, $teamData);
            // 球员
            $playerWhere = [
                'out_playerid' => $val->playerId,
                'player_name' => $val->playerNameChs,
            ];
            $playerData = [
                'out_playerid' => $val->playerId,
                'player_name' => $val->playerNameChs,
                'player_name_en' => $val->playerNameEn,
                'player_name_hk' => $val->playerNameCht,
                'team_id' => $teamId
            ];
            $playerId = Fplayer::handleSection($playerWhere, $playerData);

            $where = [
                'league_id' => $leagueData['league_id'],
                'season_id' => $seasonId,
                'team_id' => $teamId,
                'player_id' => $playerId,
            ];
            $inData = [
                'league_id' => $leagueData['league_id'],
                'season_id' => $seasonId,
                'season_name' => $leagueData['cur_season'],
                'team_id' => $teamId,
                'player_id' => $playerId,
                'out_league_id' => $leagueData['out_league_id'],
                'out_team_id' => $val->teamID,
                'out_player_id' => $val->playerId,
                'team_name' => $val->teamNameChs,
                'player_name' => $val->playerNameChs,
                'goals' => $val->goals,
                'home_goals' => $val->homeGoals,
                'away_goals' => $val->awayGoals,
                'penalty_goals' => intval($val->homePenalty) + intval($val->awayPenalty),
                'home_penalty' => $val->homePenalty,
                'away_penalty' => $val->awayPenalty,
            ];

            Fshooters::handleData($where, $inData);
        }
    }

    /**
     * @param $Url
     * @param string $Method
     * @param array $Parameter
     * @param array $Header
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function send_request($Url, $Method = 'GET', $Parameter = [], $Header = []) {

        if ( !$Url ) {
            return false;
        }

        $option['connect_timeout'] = 30;
        $option['timeout'] = 30;

        if ( $Parameter ) {

            $option['query'] = $Parameter;

        }

        try {
            $client = new Client($option);
            $request = new Request($Method, $Url, $Header);
            $response = $client->send($request);
            $body = $response->getBody();
            $content = $body->getContents();
            $HTTP_Code = $response->getStatusCode();
        } catch (RequestException $e) {
            $content = $e->getMessage();
            $HTTP_Code = $e->getCode();

        }

        return [ 'content' => $content, 'HTTP_Code' => $HTTP_Code ];
    }
}