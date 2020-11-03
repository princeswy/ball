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
use App\models\Fmatch;
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

class missPlayerTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:missPlayerTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探球员伤停、赛前简报数据';
    public static $Url = 'http://interface.win007.com/football/injury.aspx?language=cn';
    public static $source = 'win007';

    public function handle () {
        $script_name = substr($this->signature,0,strpos($this->signature,' '));
        check_process_num($script_name) || exit('Process limit');
        $res = self::send_request(self::$Url);
        $resData = json_decode($res['content']);
        foreach ($resData->list as $key => $val) {
//            $matchData = Fmatch::where('out_match_id', $val->matchId)->first();
//            $matchId = $matchData ? $matchData->toArray()['match_id'] : 0;
            if ($val->playerSuspend) {
                foreach ($val->playerSuspend as $p_k => $p_v) {
                    $teamData = Fteam::where('out_teamid', $p_v->teamId)->first();
                    $teamId = $teamData ? $teamData->toArray()['team_id'] : 0;
                    $playerWhere = [
                        'out_playerid'  =>  $p_v->playerId
                    ];
                    $playerData = [
                        'out_playerid'  =>  $p_v->playerId,
                        'player_name'   =>  $p_v->playerNameChs,
                        'player_name_en'   =>  $p_v->playerNameEn,
                        'player_name_hk'   =>  $p_v->playerNameCht
                    ];
                    $playerId = Fplayer::handleSection($playerWhere, $playerData);

                    $where = [
                        'team_id' => $teamId,
                        'out_team_id' => $p_v->teamId,
                        'player_id' => $playerId,
                        'out_player_id' => $p_v->playerId
                    ];
                    $data = [
                        'team_id' => $teamId,
                        'out_team_id' => $p_v->teamId,
                        'player_id' => $playerId,
                        'out_player_id' => $p_v->playerId,
                        'player_name' => $p_v->playerNameChs,
                        'player_status_name' => $p_v->reason,
                        'status_type' => $p_v->reasonType,
                        'source' => self::$source,
                    ];

                    Fmissplayer::handleSection($where, $data);
                }
            }
        }
        $this->info('共处理'.count($resData->list).'场比赛数据');
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