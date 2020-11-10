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
        $script_name = $this->signature;
        $league_id = $this->option('league_id');
        if ($league_id) {
            $script_name = $script_name. '--league_id'.$league_id;
        }
        self::check_process_num($script_name) || exit('Process limit');

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

            if ($val->isHome) {
                $homeDataMap[] = $inData;
            } else {
                $guestDataMap[] = $inData;
            }

            FplayerCount::handleData($where, $inData);
        }
        if (count($homeDataMap) > count($guestDataMap)) {
            foreach ($homeDataMap as $h_key => $h_val) {
                $h_val['is_home'] = 2;
                foreach ($guestDataMap as $g_key => $g_val) {
                    $allWhere = [
                        'league_id' => $h_val['league_id'],
                        'season_id' => $h_val['season_id'],
                        'team_id' => $h_val['team_id'],
                        'player_id' => $h_val['player_id'],
                        'is_home' => 2,
                    ];
                    if ($h_val['out_player_id'] == $g_val['out_player_id']) {
                        $h_val['appearanceNum'] = (int) $h_val['appearanceNum'] + (int) $g_val['appearanceNum'];
                        $h_val['substituteNum'] = (int) $h_val['substituteNum'] + (int) $g_val['substituteNum'];
                        $h_val['playingTime'] = (int) $h_val['playingTime'] + (int) $g_val['playingTime'];
                        $h_val['goals'] = (int) $h_val['goals'] + (int) $g_val['goals'];
                        $h_val['penaltyGoals'] = (int) $h_val['penaltyGoals'] + (int) $g_val['penaltyGoals'];
                        $h_val['shots'] = (int) $h_val['shots'] + (int) $g_val['shots'];
                        $h_val['shotsTarget'] = (int) $h_val['shotsTarget'] + (int) $g_val['shotsTarget'];
                        $h_val['wasFouled'] = (int) $h_val['wasFouled'] + (int) $g_val['wasFouled'];
                        $h_val['offsides'] = (int) $h_val['offsides'] + (int) $g_val['offsides'];
                        $h_val['bestSum'] = (int) $h_val['bestSum'] + (int) $g_val['bestSum'];
                        $h_val['totalPass'] = (int) $h_val['totalPass'] + (int) $g_val['totalPass'];
                        $h_val['passSuc'] = (int) $h_val['passSuc'] + (int) $g_val['passSuc'];
                        $h_val['keyPass'] = (int) $h_val['keyPass'] + (int) $g_val['keyPass'];
                        $h_val['assist'] = (int) $h_val['assist'] + (int) $g_val['assist'];
                        $h_val['longBall'] = (int) $h_val['longBall'] + (int) $g_val['longBall'];
                        $h_val['longBallsSuc'] = (int) $h_val['longBallsSuc'] + (int) $g_val['longBallsSuc'];
                        $h_val['throughBall'] = (int) $h_val['throughBall'] + (int) $g_val['throughBall'];
                        $h_val['throughBallSuc'] = (int) $h_val['throughBallSuc'] + (int) $g_val['throughBallSuc'];
                        $h_val['dribblesSuc'] = (int) $h_val['dribblesSuc'] + (int) $g_val['dribblesSuc'];
                        $h_val['crossNum'] = (int) $h_val['crossNum'] + (int) $g_val['crossNum'];
                        $h_val['crossSuc'] = (int) $h_val['crossSuc'] + (int) $g_val['crossSuc'];
                        $h_val['tackles'] = (int) $h_val['tackles'] + (int) $g_val['tackles'];
                        $h_val['interception'] = (int) $h_val['interception'] + (int) $g_val['interception'];
                        $h_val['clearance'] = (int) $h_val['clearance'] + (int) $g_val['clearance'];
                        $h_val['dispossessed'] = (int) $h_val['dispossessed'] + (int) $g_val['dispossessed'];
                        $h_val['shotsBlocked'] = (int) $h_val['shotsBlocked'] + (int) $g_val['shotsBlocked'];
                        $h_val['aerialSuc'] = (int) $h_val['aerialSuc'] + (int) $g_val['aerialSuc'];
                        $h_val['fouls'] = (int) $h_val['fouls'] + (int) $g_val['fouls'];
                        $h_val['red'] = (int) $h_val['red'] + (int) $g_val['red'];
                        $h_val['yellow'] = (int) $h_val['yellow'] + (int) $g_val['yellow'];
                        $h_val['turnover'] = (int) $h_val['turnover'] + (int) $g_val['turnover'];
                    }

                    FplayerCount::handleData($allWhere, $h_val);
                }
            }
        } else {
            foreach ($guestDataMap as $g_key=> $g_val) {
                $g_val['is_home'] = 2;
                foreach ($homeDataMap as $h_key  => $h_val) {
                    $allWhere = [
                        'league_id' => $g_val['league_id'],
                        'season_id' => $g_val['season_id'],
                        'team_id' => $g_val['team_id'],
                        'player_id' => $g_val['player_id'],
                        'is_home' => 2,
                    ];
                    if ($g_val['out_player_id'] == $g_val['out_player_id']) {
                        $g_val['appearanceNum'] = (int) $g_val['appearanceNum'] + (int) $h_val['appearanceNum'];
                        $g_val['substituteNum'] = (int) $g_val['substituteNum'] + (int) $h_val['substituteNum'];
                        $g_val['playingTime'] = (int) $g_val['playingTime'] + (int) $h_val['playingTime'];
                        $g_val['goals'] = (int) $g_val['goals'] + (int) $h_val['goals'];
                        $g_val['penaltyGoals'] = (int) $g_val['penaltyGoals'] + (int) $h_val['penaltyGoals'];
                        $g_val['shots'] = (int) $g_val['shots'] + (int) $h_val['shots'];
                        $g_val['shotsTarget'] = (int) $g_val['shotsTarget'] + (int) $h_val['shotsTarget'];
                        $g_val['wasFouled'] = (int) $g_val['wasFouled'] + (int) $h_val['wasFouled'];
                        $g_val['offsides'] = (int) $g_val['offsides'] + (int) $h_val['offsides'];
                        $g_val['bestSum'] = (int) $g_val['bestSum'] + (int) $h_val['bestSum'];
                        $g_val['totalPass'] = (int) $g_val['totalPass'] + (int) $h_val['totalPass'];
                        $g_val['passSuc'] = (int) $g_val['passSuc'] + (int) $h_val['passSuc'];
                        $g_val['keyPass'] = (int) $g_val['keyPass'] + (int) $h_val['keyPass'];
                        $g_val['assist'] = (int) $g_val['assist'] + (int) $h_val['assist'];
                        $g_val['longBall'] = (int) $g_val['longBall'] + (int) $h_val['longBall'];
                        $g_val['longBallsSuc'] = (int) $g_val['longBallsSuc'] + (int) $h_val['longBallsSuc'];
                        $g_val['throughBall'] = (int) $g_val['throughBall'] + (int) $h_val['throughBall'];
                        $g_val['throughBallSuc'] = (int) $g_val['throughBallSuc'] + (int) $h_val['throughBallSuc'];
                        $g_val['dribblesSuc'] = (int) $g_val['dribblesSuc'] + (int) $h_val['dribblesSuc'];
                        $g_val['crossNum'] = (int) $g_val['crossNum'] + (int) $h_val['crossNum'];
                        $g_val['crossSuc'] = (int) $g_val['crossSuc'] + (int) $h_val['crossSuc'];
                        $g_val['tackles'] = (int) $g_val['tackles'] + (int) $h_val['tackles'];
                        $g_val['interception'] = (int) $g_val['interception'] + (int) $h_val['interception'];
                        $g_val['clearance'] = (int) $g_val['clearance'] + (int) $h_val['clearance'];
                        $g_val['dispossessed'] = (int) $g_val['dispossessed'] + (int) $h_val['dispossessed'];
                        $g_val['shotsBlocked'] = (int) $g_val['shotsBlocked'] + (int) $h_val['shotsBlocked'];
                        $g_val['aerialSuc'] = (int) $g_val['aerialSuc'] + (int) $h_val['aerialSuc'];
                        $g_val['fouls'] = (int) $g_val['fouls'] + (int) $h_val['fouls'];
                        $g_val['red'] = (int) $g_val['red'] + (int) $h_val['red'];
                        $g_val['yellow'] = (int) $g_val['yellow'] + (int) $h_val['yellow'];
                        $g_val['turnover'] = (int) $g_val['turnover'] + (int) $h_val['turnover'];
                    }

                    FplayerCount::handleData($allWhere, $g_val);
                }
            }
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

    public static function check_process_num($script_name) {
        $cmd = @popen("ps -ef | grep '{$script_name}' | grep -v grep | wc -l", 'r');
        $num = @fread($cmd, 512);
        (int) $num += 0;
        @pclose($cmd);
        if ($num > 1) {
            return false;
        }
        return true;
    }

}