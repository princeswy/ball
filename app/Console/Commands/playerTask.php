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
use App\models\Fteam;
use App\models\Task;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use MongoDB\Driver\Manager;

class playerTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:playerTask {--team_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探球员数据';
    public static $Url = 'http://interface.win007.com/football/player.aspx';

    public function handle () {
        if ($this->option('team_id')) {
            $teamIdList = explode(',', $this->option('team_id'));
            $chunk_teamIdList = array_chunk($teamIdList, 10);
        } else {
            // 获取有球员数据的球队
            $teamUrl = self::$Url.'?cmd=teamlist';
            $res = self::send_request($teamUrl);
            $resData = json_decode($res['content']);
            $teamIdList = $resData->teamIDList;
            $chunk_teamIdList = array_chunk($teamIdList, 10);
        }
        foreach ($chunk_teamIdList as $chunk_key => $chunk_val) {
            $this->info('共'.count($teamIdList).'个球队，分为'.count($chunk_teamIdList).'组，正在处理第'.($chunk_key + 1).'组');
            $teamids = implode(',', $chunk_val);
            $url = self::$Url.'?teamId='.$teamids;
            $res = self::send_request($url);
            $resData = json_decode($res['content']);
            foreach ($resData->playerList as $key => $val) {
                $playerWhere = [
                    'out_playerid'  =>  $val->playerId,
                    'player_name'   =>  $val->nameChs,
                ];
                $playerData = [
                    'out_playerid'  =>  $val->playerId,
                    'player_name'   =>  $val->nameChs,
                    'player_name_en'   =>  $val->nameEn,
                    'player_name_hk'   =>  $val->nameCht,
                    'shirt_num'   =>  $val->number,
                    'player_fullname'   =>  $val->nameChs,
                    'position'   =>  $val->positionCn,
                    'weight'   =>  $val->weight,
                    'height'   =>  $val->height,
                    'birth'   =>  $val->birthday,
                    'preferred_foot'   =>  $val->feetCn,
                    'nationality'   =>  $val->countryCn,
                    'number'   =>  $val->value,
                    'info'   =>  $val->introduceCn,
                    'info_en'   =>  $val->introduceEn,
                    'logo'   =>  $val->photo ? $val->photo.'?win007=sell' : '',
                ];
                // 获取球队ID
                $teamId = 0;
                $teamData = Fteam::where('out_teamid', $val->teamID)->first();
                if($teamData) {
                    $teamId = $teamData->toArray()['team_id'];
                }
                $playerData['team_id'] = $teamId;
                // 保存到数据库
                Fplayer::handleSection($playerWhere, $playerData);
            }
            $this->info('第'.($chunk_key + 1).'组处理完成');
            if ($chunk_key + 1 === count($chunk_teamIdList)) {
                $this->info('处理结束');
                break;
            }
            $this->warn('程序暂停90秒');
            sleep(90);
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