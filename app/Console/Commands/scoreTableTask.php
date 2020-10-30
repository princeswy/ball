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
use App\models\Fgroup;
use App\models\Fgym;
use App\models\Fleague;
use App\models\Fmanager;
use App\models\Freferee;
use App\models\FscoreTable;
use App\models\Fseason;
use App\models\Fsection;
use App\models\Fteam;
use App\models\Task;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class scoreTableTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:scoreTableTask {--league_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探联赛积分榜数据';
    public static $Url = 'http://interface.win007.com/football/standing.aspx';

    public function handle () {
        $url = self::$Url;
        $leagueMap = [];
        $leagueId = $this->option('league_id');
        if ($leagueId) {
            $league = Fleague::where('league_id', $leagueId)->first();
            if (!$league) {
                $this->error('传入的联赛ID有误，请确认无误后再执行');
                exit;
            }
            $leagueMap = [$league->toArray()];
        } else {
            $leagueMap = Fleague::get()->toArray();
        }
        $this->info('共'.count($leagueMap).'个联赛的数据。');
        foreach ($leagueMap as $league_key => $league_val) {
            $this->info('正在处理第'.($league_key + 1).'个赛事-------'.$league_val['league_name']);
            $url = self::$Url.'?leagueId='.$league_val['out_league_id'];
            if ($league_val['sub_id']) {
                $url = $url.'&subId='.$league_val['sub_id'];
            }
            $this->handleData($url, $league_val);
            if (count($leagueMap) === ($league_key + 1)) {
                continue;
            }
            $this->info('第'.count($leagueMap).'--->'.($league_key + 1).' 个赛事处理完毕，程序暂停5秒');
            sleep(5);
        }
        $this->info('处理结束，共'.count($leagueMap).'个联赛的数据。');
    }

    public function handleData($url, $leagueData) {
        $res = self::send_request($url);
        if ($res['content'] === 'No Data.') {
            $this->warn('该赛事暂无积分榜数据');
            return false;
            exit;
        }
        $resData = json_decode($res['content']);
//        dd(isset($resData->leagueInfo), $resData);
        // 联赛
        if ($leagueData['league_type'] === 1 && isset($resData->leagueInfo)) {
            // 升降级规则数据
            $leagueColorInfo = $resData->leagueColorInfos;
            // 联赛数据
            $leagueInfo = $resData->leagueInfo;
            // 球队数据
            $teamInfo = $resData->teamInfo;
            // 赛季
            $seasonData = [
                'league_id' => $leagueData['league_id'],
                'out_league_id' => $leagueData['out_league_id'],
                'season_name' => $leagueInfo->season,
            ];
            $seasonId = Fseason::handleSeason($seasonData);
            // 球队
            $teamIdMap = [];
            $teamNameMap = [];
            foreach ($teamInfo as $key => $val) {
                $teamWhere = [
                    'out_teamid' => $val->teamId,
                    'team_name' => $val->nameChs,
                ];
                $teamData = [
                    'out_teamid' => $val->teamId,
                    'team_name' => $val->nameChs,
                    'team_name_hk' => $val->nameCht,
                    'team_name_en' => $val->nameEn,
                ];
                $teamId = Fteam::handleSection($teamWhere, $teamData);
                $teamIdMap[$val->teamId] = $teamId;
                $teamNameMap[$val->teamId] = $val->nameChs;
            }
            // 处理全场积分榜数据
            foreach ($resData->totalStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 0,
                    'is_host' => 0,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 0,
                    'is_host' => 0,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                    'red_card' => $val->redCard || 0,
                    'color' => isset($val->color) && $val->color > -1 ? $leagueColorInfo[$val->color]->color : '',
                    'info' => isset($val->color) && $val->color > -1 ? $leagueColorInfo[$val->color]->leagueNameChs : '',
                    'deduction' => $val->deduction,
                    'deduction_info' => $val->deductionExplainCn
                ];
                FscoreTable::handleSection($where, $inData);
            }
            // 处理半场积分榜数据
            foreach ($resData->halfStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 1,
                    'is_host' => 0,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 1,
                    'is_host' => 0,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                ];
                FscoreTable::handleSection($where, $inData);
            }
            // 处理主场全场积分榜数据
            foreach ($resData->homeStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 0,
                    'is_host' => 1,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 0,
                    'is_host' => 1,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                ];
                FscoreTable::handleSection($where, $inData);
            }
            // 处理客场全场积分榜数据
            foreach ($resData->awayStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 0,
                    'is_host' => 2,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 0,
                    'is_host' => 2,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                ];
                FscoreTable::handleSection($where, $inData);
            }
            // 处理主场半场积分榜数据
            foreach ($resData->homeHalfStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 1,
                    'is_host' => 1,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 1,
                    'is_host' => 1,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                ];
                FscoreTable::handleSection($where, $inData);
            }
            // 处理客场半场积分榜数据
            foreach ($resData->awayHalfStandings as $key => $val) {
                $where = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueInfo->leagueId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'out_team_id' => $val->teamId,
                    'season_id' => $seasonId,
                    'type' => 1,
                    'is_host' => 2,
                ];
                $inData = [
                    'league_id' => $leagueData['league_id'],
                    'season_id' => $seasonId,
                    'team_id' => $teamIdMap[$val->teamId],
                    'team_name' => $teamNameMap[$val->teamId],
                    'out_league_id' => $leagueInfo->leagueId,
                    'out_team_id' => $val->teamId,
                    'count_turn' => isset($leagueInfo->countRound) ? $leagueInfo->countRound : 0,
                    'cur_turn' => isset($leagueInfo->currRound) ? $leagueInfo->currRound : 0,
                    'league_name' => $leagueInfo->nameChsShort,
                    'season_name' => $leagueInfo->season,
                    'type' => 1,
                    'is_host' => 2,
                    'rank' => $val->rank,
                    'points' => $val->integral,
                    'total_count' => $val->totalCount,
                    'win_count' => $val->winCount,
                    'draw_count' => $val->drawCount,
                    'lost_count' => $val->loseCount,
                    'win_rate' => $val->winRate,
                    'draw_rate' => $val->drawRate,
                    'lost_rate' => $val->loseRate,
                    'win_avg' => $val->winAverage,
                    'lost_avg' => $val->loseAverage,
                    'get_score' => $val->getScore,
                    'lost_score' => $val->loseScore,
                    'goal_diff' => $val->goalDifference,
                ];
                FscoreTable::handleSection($where, $inData);
            }
        }
        // 杯赛
        if ($leagueData['league_type'] === 2 || isset($resData->list)) {
            // 列表
            foreach ($resData->list as $li_key => $li_val) {
                // 赛季
                $seasonData = [
                    'league_id' => $leagueData['league_id'],
                    'out_league_id' => $leagueData['out_league_id'],
                    'season_name' => $li_val->season,
                ];
                $seasonId = Fseason::handleSeason($seasonData);
                // 阶段
                foreach ($li_val->score as $score_key => $score_val) {
                    // 阶段
                    $sectionWhere = [
                        'season_id' => $seasonId,
                        'section_name' => $score_val->groupNameChs,
                    ];
                    $sectionData = [
                        'season_id' => $seasonId,
                        'section_name' => $score_val->groupNameChs,
                    ];
                    $sectionId = Fsection::handleSection($sectionWhere, $sectionData);
                    // 分组
                    foreach ($score_val->groupScore as $group_key => $group_val) {
                        $groupData = [
                            'section_id' => $sectionId,
                            'group_name' => $group_val->groupCn
                        ];
                        $groupId = Fgroup::handleSection($groupData, $groupData);
                        // 积分数据
                        foreach ($group_val->scoreItems as $key => $val) {
                            $teamData = [
                                'out_teamid' => $val->teamId,
                                'team_name' => $val->teamNameChs,
                                'team_name_hk' => $val->teamNameCht,
                                'team_name_en' => $val->teamNameEn,
                            ];
                            $teamId = Fteam::handleSection($teamData, $teamData);
                            $where = [
                                'league_id' => $leagueData['league_id'],
                                'out_league_id' => $li_val->leagueId,
                                'group_id' => $groupId,
                                'team_id' => $teamId,
                                'out_team_id' => $val->teamId,
                                'season_id' => $seasonId,
                                'type' => 0,
                                'is_host' => 0,
                            ];
                            $inData = [
                                'league_id' => $leagueData['league_id'],
                                'season_id' => $seasonId,
                                'group_id' => $groupId,
                                'team_id' => $teamId,
                                'team_name' => $val->teamNameChs,
                                'out_league_id' => $li_val->leagueId,
                                'out_team_id' => $val->teamId,
                                'league_name' => $leagueData['league_name'],
                                'season_name' => $li_val->season,
                                'type' => 0,
                                'is_host' => 0,
                                'rank' => $val->rank,
                                'points' => $val->integral,
                                'total_count' => $val->totalCount,
                                'win_count' => $val->winCount,
                                'draw_count' => $val->drawCount,
                                'lost_count' => $val->loseCount,
                                'get_score' => $val->getScore,
                                'lost_score' => $val->loseScore,
                                'goal_diff' => $val->goalDifference,
                                'color' => $val->color
                            ];
                            FscoreTable::handleSection($where, $inData);
                        }
                    }
                }
            }
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