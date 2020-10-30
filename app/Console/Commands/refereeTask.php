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
use App\models\Freferee;
use App\models\Fseason;
use App\models\Fsection;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class refereeTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:refereeTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探裁判数据';
    public static $Url = 'http://interface.win007.com/football/referee.aspx';

    public function handle () {
        $res = self::send_request(self::$Url);
        $resData = json_decode($res['content']);
        foreach ($resData->list as $key => $val) {
            $where = [
                'out_refereeid' => $val->refereeId,
                'full_name' => $val->nameChs
            ];
            $data = [
                'out_refereeid' => $val->refereeId,
                'full_name' => $val->nameChs,
                'full_name_en' => $val->nameEn,
                'full_name_zht' => $val->nameCht,
                'birthday' => $val->birthday,
                'nationality' => $val->countryChs,

            ];
            Freferee::handleReferee($where, $data);
        }
        $this->info('共处理'.count($resData->list).'条数据');
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
            'league_id' => $data->league_id,
            'season_name' => $data->season_name
        ];
        $seasonId = Fseason::updateOrCreate($where, $data)->season_id;
        return $seasonId;
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