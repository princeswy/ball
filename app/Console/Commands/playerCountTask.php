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
use App\models\FplayerCount;
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

class PlayerCountTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:playerCountTask {--league_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探球员详细技术统计数据';
    public static $Url = 'http://interface.win007.com/football/playerCount.aspx';

    public function handle () {
        $league_id = $this->option('league_id');
        $leagueMap = [];
        if ($league_id) {
            $league = Fleague::where('league_id', $league_id)->first();
            if (!$league) {
                $this->error('传入的联赛ID有误，请确认无误后再执行');
                exit;
            }
            $leagueMap = [$league->toArray()];
        } else {
            $res = self::send_request(self::$Url);
            $resData = json_decode($res['content']);
            if (!isset($resData->list) || count($resData->list) == 0) {
                $this->error('暂无数据处理');
                exit;
            }
            foreach ($resData->list as $key => $val) {
                $leagueData = Fleague::where('out_league_id', $val->leagueId)->first();
                if (!$leagueData) {
                    continue;
                }
                $leagueData = $leagueData->toArray();
                $leagueMap[] = [
                    'league_id' => $leagueData['league_id'],
                    'league_name' => $leagueData['league_name'],
                    'out_league_id' => $val->leagueId,
                    'cur_season' => $val->season
                ];
            }
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
        $homeDataMap = [];
        $guestDataMap = [];
        foreach ($resData->list as $key => $val) {
            // 赛季
            $seasonData = [
                'league_id' => $leagueData['league_id'],
                'out_league_id' => $leagueData['out_league_id'],
                'season_name' => $leagueData['cur_season'],
            ];
            $seasonId = Fseason::handleSeason($seasonData);
            // 球队
            $teamData = Fteam::where('out_teamid', $val->teamId)->first();
            $teamId = $teamData ? $teamData->toArray()['team_id'] : 0;
            // 球员
            $playerData = Fplayer::where('out_playerid', $val->playerId)->first();
            $playerId = $playerData ? $playerData->toArray()['id'] : 0;
            if ($val->isHome) {
                $homeDataMap[$val->playerId] = $val;
            } else {
                $guestDataMap[$val->playerId] = $val;
            }
            continue;

            $where = [
                'league_id' => $leagueData['league_id'],
                'season_id' => $seasonId,
                'team_id' => $teamId,
                'player_id' => $playerId,
                'is_home' => $val->isHome ? 1 : 0,
            ];
            $inData = [
                'league_id' => $leagueData['league_id'],
                'season_id' => $seasonId,
                'league_name' => $leagueData['league_name'],
                'season_name' => $leagueData['cur_season'],
                'team_id' => $teamId,
                'player_id' => $playerId,
                'out_league_id' => $leagueData['out_league_id'],
                'out_team_id' => $val->teamId,
                'out_player_id' => $val->playerId,
                'is_home' => $val->isHome ? 1 : 0,
                'appearanceNum' => $val->appearanceNum,
                'substituteNum' => $val->substituteNum,
                'playingTime' => $val->playingTime,
                'goals' => $val->goals,
                'penaltyGoals' => $val->penaltyGoals,
                'shots' => $val->shots,
                'shotsTarget' => $val->shotsTarget,
                'wasFouled' => $val->wasFouled,
                'offsides' => $val->offsides,
                'bestSum' => $val->bestSum,
                'rating' => $val->rating,
                'totalPass' => $val->totalPass,
                'passSuc' => $val->passSuc,
                'keyPass' => $val->keyPass,
                'assist' => $val->assist,
                'longBall' => $val->longBall,
                'longBallsSuc' => $val->longBallsSuc,
                'throughBall' => $val->throughBall,
                'throughBallSuc' => $val->throughBallSuc,
                'dribblesSuc' => $val->dribblesSuc,
                'crossNum' => $val->crossNum,
                'crossSuc' => $val->crossSuc,
                'tackles' => $val->tackles,
                'interception' => $val->interception,
                'clearance' => $val->clearance,
                'dispossessed' => $val->dispossessed,
                'shotsBlocked' => $val->shotsBlocked,
                'aerialSuc' => $val->aerialSuc,
                'fouls' => $val->fouls,
                'red' => $val->red,
                'yellow' => $val->yellow,
                'turnover' => $val->turnover,
                'modifyTime' => $val->modifyTime
            ];

            FplayerCount::handleData($where, $inData);
        }
        dd(count($homeDataMap), count($guestDataMap));
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