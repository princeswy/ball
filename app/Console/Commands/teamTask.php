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

class TeamTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:teamTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探球队数据';
    public static $Url = 'http://interface.win007.com/football/team.aspx';

    public function handle () {
//        $page = Task::where('task_name', 'team')->first()->toArray()['page'];
//        $this->info('正在处理第'.$page.'页数据');
//        $url = self::$Url.'?page='.$page;
        $url = self::$Url;
        $res = self::send_request($url);
        $resData = json_decode($res['content']);
        foreach ($resData->teamList as $key => $val) {
            $teamWhere = [
                'out_teamid' => $val->teamId
            ];
            $teamData = [
                'out_teamid' => $val->teamId,
                'team_name' => $val->nameChs,
                'team_name_hk' => $val->nameCht,
                'team_name_en' => $val->nameEn,
                'full_name' => $val->nameChs,
                'full_name_hk' => $val->nameCht,
                'full_name_en' => $val->nameEn,
                'logo_path' => $val->logo ? $val->logo.'?win007=sell' : '',
                'info' => $val->teamId,
                'found_time' => $val->foundingDate,
                'area' => $val->addrCn,
                'url' => $val->website,
            ];
            // 获取联赛ID
            $leagueId = Fleague::where('out_league_id', $val->leagueId)->first()->toArray()['league_id'];
            $teamData['league_id'] = $leagueId;
            // 获取国家ID
            $countryId = Country::handleSection(['country_name' => $val->areaCn], ['country_name' => $val->areaCn, 'country_name_en' => $val->areaEn]);
            $teamData['country_id'] = $countryId;
            // 场馆ID
            $gymData = [
                'gym_name' => $val->gymCn,
                'gym_name_en' => $val->gymEn,
                'capacity' => $val->capacity
            ];
            $gymId = Fgym::handleSection(['gym_name' => $val->gymCn], $gymData);
            $teamData['gym_id'] = $gymId;
            // 主教练ID
            $manangerId = Fmanager::handleSection(['full_name' => $val->coachCn], ['full_name' => $val->coachCn]);
            $teamData['master_id'] = $manangerId;
            Fteam::handleSection($teamWhere, $teamData);
        }
//        $updatePage = $page + 1;
//        if ($updatePage >= 5) {
//            $updatePage = 1;
//        }
//        Task::where('task_name', 'team')->update(['page' => $updatePage]);
        $this->info('共处理'.count($resData->teamList).'条数据');
        sleep(90);
        $this->handle();
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