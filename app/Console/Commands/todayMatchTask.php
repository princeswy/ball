<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Fgroup;
use App\models\Fgym;
use App\models\Fleague;
use App\models\Fmatch;
use App\models\Fseason;
use App\models\Fsection;
use App\models\Fteam;
use App\models\Fturn;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class todayMatchTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:todayMatchTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探当天比赛数据';
    public static $Url = 'http://interface.win007.com/football/today.aspx';

    public function handle () {
        $url = self::$Url;
        $res = self::send_request($url);
        $resData = json_decode($res['content']);
        $this->info('共'.count($resData->matchList).'条数据，开始处理');
        if ($resData->matchList) foreach ($resData->matchList as $key => $val) {
            $this->info($key + 1);
            // 获取联赛ID
            $league = Fleague::where('out_league_id', $val->leagueId)->first();
            if (!$league) {
                continue;
            }
            $leagueData = $league->toArray();
            $leagueId = $leagueData['league_id'];
            $leagueType = $leagueData['league_type'];
            $leagueName = $leagueData['league_name'];
            // 赛季数据
            $seasonWhere = [
                'league_id' => $leagueId,
                'season_name' => $val->season
            ];
            $seasonData = [
                'league_id' => $leagueId,
                'out_league_id' => $val->leagueId,
                'season_name' => $val->season
            ];
            $seasonId = Fseason::handleSeason($seasonWhere, $seasonData);
            // 阶段数据
            $sectionWhere = [
                'out_section_id' => $val->subLeagueId,
                'season_id' => $seasonId,
                'section_name' => $val->subLeagueChs
            ];
            $sectionData = [
                'out_section_id' => $val->subLeagueId,
                'season_id' => $seasonId,
                'section_name' => $val->subLeagueChs
            ];
            $sectionId = Fsection::handleSection($sectionWhere, $sectionData);
            // 分组
            $groupName = '';
            if ($val->grouping) {
                $groupName = $val->grouping;
            } else {
                if ($leagueType === 1) {
                    $groupName = '联赛';
                }
            }
            $groupId = Fgroup::handleSection(['section_id' => $sectionId, 'group_name' => $groupName], ['section_id' => $sectionId, 'group_name' => $groupName]);
            // 轮次
            $turnData = [
                'group_id' => $groupId,
                'turn_name' => $val->roundCn
            ];
            $turnId = Fturn::handleSection($turnData, $turnData);
            // 场馆
            $gymId = Fgym::handleSection(['gym_name' => $val->locationCn], ['gym_name' => $val->locationCn, 'gym_name_en' => $val->locationEn]);
            $homeData = [
                'out_teamid' => $val->homeId,
                'team_name' => $val->homeChs,
                'team_name_hk' => $val->homeCht,
                'team_name_en' => $val->homeEn,
            ];
            $guestData = [
                'out_teamid' => $val->awayId,
                'team_name' => $val->awayChs,
                'team_name_hk' => $val->awayCht,
                'team_name_en' => $val->awayEn,
            ];
            $homeId = Fteam::handleSection(['out_teamid' => $val->homeId], $homeData);
            $guestId = Fteam::handleSection(['out_teamid' => $val->awayId], $guestData);
            $matchWhere = [
                'league_id' => $leagueId,
                'out_match_id' => $val->matchId,
                'home_name' => $val->homeChs,
                'guest_name' => $val->awayChs
            ];
            $matchData = [
                'out_match_id' => $val->matchId,
                'home_name' => $val->homeChs,
                'guest_name' => $val->awayChs,
                'match_time' => $val->matchTime,
                'match_state' => $val->state,
                'half_score' => (($val->state > 0 && $val->state <=5) || $val->state === -1) ? $val->homeHalfScore.'-'.$val->awayHalfScore : '',
                'score' => $val->state === -1 ? $val->homeScore.'-'.$val->awayScore : '',
                'zl' => $val->isNeutral ? 1 : 0,
                'home_id' => $homeId,
                'guest_id' => $guestId,
                'out_home_id' => $val->homeId,
                'out_guest_id' => $val->awayId,
                'season_id' => $seasonId,
                'section_id' => $sectionId,
                'group_id' => $groupId,
                'turn_id' => $turnId,
                'gym_id' => $gymId,
                'temperature' => $val->temp,
                'weather' => $val->weatherCn,
                'home_red' => $val->homeRed,
                'guest_red' => $val->awayRed,
                'home_yellow' => $val->homeYellow,
                'guest_yellow' => $val->awayYellow,
                'home_corner' => $val->homeCorner,
                'guest_corner' => $val->awayCorner,
                'home_rank' => $val->homeRankCn,
                'guest_rank' => $val->awayRankCn,
                'is_lineup' => $val->hasLineup ? 1 : 0,
                'league_name' => $leagueName ? $leagueName : $val->leagueChsShort
            ];
            Fmatch::handleSection($matchWhere, $matchData);
        }
        $this->info('共处理'.count($resData->matchList).'条数据');
    }

    /**
     *获取球队ID
     */
    public static function getTeamId($outTeamId) {
        $team = Fteam::where('out_teamid', $outTeamId)->first();
        if (!$team) {
            return 0;
        }
        $teamId = $team->toArray()['team_id'];
        return $teamId;
    }

    /**
     *获取赛季ID
     */
    public static function getSeasonId($leagueId, $seasonName) {
        $team = Fseason::where(['league_id' => $leagueId, 'season_name' => $seasonName ])->first();
        if (!$team) {
            return 0;
        }
        $teamId = $team->toArray()['team_id'];
        return $teamId;
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