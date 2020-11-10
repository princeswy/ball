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
use App\models\Fformation;
use App\models\Fgym;
use App\models\Fleague;
use App\models\Fmanager;
use App\models\Fmatch;
use App\models\FmatchLineup;
use App\models\Fmissplayer;
use App\models\Fplayer;
use App\models\Freferee;
use App\models\Fseason;
use App\models\Fsection;
use App\models\Fteam;
use App\models\Task;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use MongoDB\Driver\Manager;

class lineUpTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:lineUpTask {--match_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探阵容数据';
    public static $Url = 'http://interface.win007.com/football/lineup.aspx';
    public static $source = 'win007';

    public function handle () {
        $url = self::$Url;
        $matchId = $this->option('match_id');
        $script_name = $this->signature;
        if ($matchId) {
            $script_name = $script_name.' --match_id='.$matchId;
        }
        self::check_process_num($script_name) || exit('Process limit');
        if ($matchId) {
            $matchData = Fmatch::where('match_id', $matchId)->first();
            $outMatchId = $matchData ? $matchData->toArray()['out_match_id'] : false;
            if (!$matchData || !$outMatchId) {
                $this->error('您输入的比赛ID有误');
                exit;
            }
            $url = self::$Url.'?matchId='.$outMatchId;
        }
        $res = self::send_request($url);
        $resData = json_decode($res['content']);
        foreach ($resData->lineupList as $key => $val) {
            $matchData = Fmatch::where('out_match_id', $val->matchId)->first();
            $matchId = $homeId = $guestId = $outHomeId = $outGuestId = 0;
            if ($matchData) {
                $matchData = $matchData->toArray();
                $matchId = $matchData['match_id'];
                $homeId = $matchData['home_id'];
                $outHomeId = $matchData['out_home_id'];
                $guestId = $matchData['guest_id'];
                $outGuestId = $matchData['out_guest_id'];
            }
            // 主队阵型
            if ($val->homeArray) {
                $homefWhere = [
                    'out_match_id' => $val->matchId,
                    'out_team_id' => $outHomeId
                ];
                $homefData = [
                    'match_id' => $matchId,
                    'team_id' => $homeId,
                    'out_match_id' => $val->matchId,
                    'out_team_id' => $outHomeId,
                    'formation' => $val->homeArray
                ];
                Fformation::handleSection($homefWhere, $homefData);
            }
            // 客队阵型
            if ($val->awayArray) {
                $guestfWhere = [
                    'out_match_id' => $val->matchId,
                    'out_team_id' => $outGuestId
                ];
                $guestfData = [
                    'match_id' => $matchId,
                    'team_id' => $guestId,
                    'out_match_id' => $val->matchId,
                    'out_team_id' => $outGuestId,
                    'formation' => $val->awayArray
                ];
                Fformation::handleSection($guestfWhere, $guestfData);
            }
            // 主队首发阵容
            if ($val->homeLineup) {
                foreach ($val->homeLineup as $hl_k => $hl_v) {
                    $playerWhere = [
                        'out_playerid'  =>  $hl_v->playerId
                    ];
                    $playerData = [
                        'out_playerid'  =>  $hl_v->playerId,
                        'player_name'   =>  $hl_v->nameChs,
                        'player_name_en'   =>  $hl_v->nameEn,
                        'player_name_hk'   =>  $hl_v->nameCht,
                        'shirt_num'   =>  $hl_v->number,
                    ];
                    $playerId = Fplayer::handleSection($playerWhere, $playerData);

                    $where = [
                        'out_matchid' => $val->matchId,
                        'out_playerid' => $hl_v->playerId,
                        'player_id' => $playerId,
                        'is_host' => 1,
                        'subsitute' => 1
                    ];
                    $data = [
                        'match_id' => $matchId,
                        'out_matchid' => $val->matchId,
                        'team_id' => $homeId,
                        'player_id' => $playerId,
                        'out_playerid' => $hl_v->playerId,
                        'player_name' => $hl_v->nameChs,
                        'player_number' => $hl_v->number,
                        'is_host' => 1,
                        'subsitute' => 1,
                        'pos' => $val->homeArray ? strlen($val->homeArray).'-'.$hl_v->positionId : $hl_v->positionId,
                        'source' => self::$source,
                    ];

                    FmatchLineup::handleSection($where, $data);
                }
            }
            // 客队首发
            if ($val->awayLineup) {
                foreach ($val->awayLineup as $gl_k => $gl_v) {
                    $playerWhere = [
                        'out_playerid'  =>  $gl_v->playerId
                    ];
                    $playerData = [
                        'out_playerid'  =>  $gl_v->playerId,
                        'player_name'   =>  $gl_v->nameChs,
                        'player_name_en'   =>  $gl_v->nameEn,
                        'player_name_hk'   =>  $gl_v->nameCht,
                        'shirt_num'   =>  $gl_v->number,
                    ];
                    $playerId = Fplayer::handleSection($playerWhere, $playerData);

                    $where = [
                        'out_matchid' => $val->matchId,
                        'out_playerid' => $gl_v->playerId,
                        'player_id' => $playerId,
                        'is_host' => 2,
                        'subsitute' => 1
                    ];
                    $data = [
                        'match_id' => $matchId,
                        'out_matchid' => $val->matchId,
                        'team_id' => $homeId,
                        'player_id' => $playerId,
                        'out_playerid' => $gl_v->playerId,
                        'player_name' => $gl_v->nameChs,
                        'player_number' => $gl_v->number,
                        'is_host' => 2,
                        'subsitute' => 1,
                        'pos' => $val->homeArray ? strlen($val->homeArray).'-'.$gl_v->positionId : $gl_v->positionId,
                        'source' => self::$source,
                    ];

                    FmatchLineup::handleSection($where, $data);
                }
            }
            // 主队替补
            if ($val->homeBackup) {
                foreach ($val->homeBackup as $hb_k => $hb_v) {
                    $playerWhere = [
                        'out_playerid'  =>  $hb_v->playerId
                    ];
                    $playerData = [
                        'out_playerid'  =>  $hb_v->playerId,
                        'player_name'   =>  $hb_v->nameChs,
                        'player_name_en'   =>  $hb_v->nameEn,
                        'player_name_hk'   =>  $hb_v->nameCht,
                        'shirt_num'   =>  $hb_v->number,
                    ];
                    $playerId = Fplayer::handleSection($playerWhere, $playerData);

                    $where = [
                        'out_matchid' => $val->matchId,
                        'out_playerid' => $hb_v->playerId,
                        'player_id' => $playerId,
                        'is_host' => 1,
                        'subsitute' => 2
                    ];
                    $data = [
                        'match_id' => $matchId,
                        'out_matchid' => $val->matchId,
                        'team_id' => $homeId,
                        'player_id' => $playerId,
                        'out_playerid' => $hb_v->playerId,
                        'player_name' => $hb_v->nameChs,
                        'player_number' => $hb_v->number,
                        'is_host' => 1,
                        'subsitute' => 2,
                        'pos' => $val->homeArray ? strlen($val->homeArray).'-'.$hb_v->positionId : $hb_v->positionId,
                        'source' => self::$source,
                    ];

                    FmatchLineup::handleSection($where, $data);
                }
            }
            // 客队替补
            if ($val->awayBackup) {
                foreach ($val->awayBackup as $gb_k => $gb_v) {
                    $playerWhere = [
                        'out_playerid'  =>  $gb_v->playerId
                    ];
                    $playerData = [
                        'out_playerid'  =>  $gb_v->playerId,
                        'player_name'   =>  $gb_v->nameChs,
                        'player_name_en'   =>  $gb_v->nameEn,
                        'player_name_hk'   =>  $gb_v->nameCht,
                        'shirt_num'   =>  $gb_v->number,
                    ];
                    $playerId = Fplayer::handleSection($playerWhere, $playerData);

                    $where = [
                        'out_matchid' => $val->matchId,
                        'out_playerid' => $gb_v->playerId,
                        'player_id' => $playerId,
                        'is_host' => 2,
                        'subsitute' => 2
                    ];
                    $data = [
                        'match_id' => $matchId,
                        'out_matchid' => $val->matchId,
                        'team_id' => $homeId,
                        'player_id' => $playerId,
                        'out_playerid' => $gb_v->playerId,
                        'player_name' => $gb_v->nameChs,
                        'player_number' => $gb_v->number,
                        'is_host' => 2,
                        'subsitute' => 2,
                        'pos' => $val->homeArray ? strlen($val->homeArray).'-'.$gb_v->positionId : $gb_v->positionId,
                        'source' => self::$source,
                    ];

                    FmatchLineup::handleSection($where, $data);
                }
            }
        }
        $this->info('共处理'.count($resData->lineupList).'场比赛数据');
    }

    public static function check_process_num($script_name) {
        $cmd = @popen("ps -ef | grep '{$script_name}' | grep -v grep | wc -l", 'r');
        $num = @fread($cmd, 512);
        $num += 0;
        @pclose($cmd);
        if ($num > 1) {
            return false;
        }
        return true;
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