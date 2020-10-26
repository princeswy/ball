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
use App\models\Fleague;
use App\models\Fseason;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class LeagueTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leagueTask {--day=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探联赛数据(day--获取指定天数内发生修改的赛事资料。)';
    public static $leagueUrl = 'http://interface.win007.com/football/league.aspx';

    public function handle () {
        $day = $this->option('day');
        if ($day > 0) {
            self::$leagueUrl = self::$leagueUrl.'?day='.$day;
        }
        $leagueRes = self::send_request(self::$leagueUrl);
//        $leagueMap = (object)null;
//        if (!is_object($leagueRes['content'])) {
//            $this->error($leagueRes['content']);
//            exit;
//        }
        $leagueMap = json_decode($leagueRes['content']);
        foreach ($leagueMap->leagueList as $key => $val) {
            $league = [
                'out_league_id' =>  $val->leagueId,
                'color' =>  $val->color,
                'league_name' =>  $val->nameChsShort,
                'league_name_en' =>  $val->nameEnShort,
                'full_name' =>  $val->nameChs,
                'full_name_hk' =>  $val->nameCht,
                'full_name_en' =>  $val->nameEn,
                'league_type' =>  $val->type,
                'area_id' =>  $val->areaId,
                'logo_path' =>  $val->leagueLogo ? $val->leagueLogo.'?win007=sell' : '',
                'cur_season' => $val->currSeason,
                'all_round' => $val->sumRound,
                'cur_round' => $val->currRound
            ];
            $where = [
                'out_league_id' => $val->leagueId
            ];
            $countryData = [
                'out_country_id' => $val->countryId,
                'country_name' => $val->countryCn,
                'country_name_en' => $val->countryEn,
            ];
            // 获取自己库里的国家ID
            $country_id = $this->handleCountry($countryData);
            $league['country_id'] = $country_id;

            // 获取自己库里的区域ID
            $areaId = $this->handleContinent([ 'area_id' => $val->areaId ]);
            $league['area_id'] = $areaId;

            // 写入数据库
            $leagueId = Fleague::updateOrCreate($where, $league)->league_id;

            // 保存赛季数据
            $seasonData = [
                'league_id' => $leagueId,
                'out_league_id' => $league['out_league_id'],
                'season_name' => $leagueId['currSeason'],
                'season_name_hk' => $leagueId['currSeason']
            ];
            $this->handleSeason($seasonData);

        }
        $this->info('共处理'.count($leagueMap->leagueList).'条数据');
    }

    public function handleCountry ($data) {
        $where = [
            'out_country_id' => $data['out_country_id']
        ];
        $country_id = Country::updateOrCreate($where, $data)->country_id;
        return $country_id;
    }

    public function handleContinent ($data) {
        $continentMap = [
            '国际',
            '欧洲',
            '美洲',
            '亚洲',
            '大洋洲',
            '非洲'
        ];
        $where = [
            'out_continent_id' => $data['area_id']
        ];
        $inData = [
            'out_continent_id' => $data['area_id'],
            'continent_name' => $continentMap[$data['area_id']]
        ];
        $continentId = Continent::updateOrCreate($where, $inData)->continent_id;
        return $continentId;
    }

    public function handleSeason ($data) {
        $where = [
            'league_id' => $data['league_id'],
            'season_name' => $data['season_name']
        ];
        Fseason::updateOrCreate($where, $data);
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