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
use App\models\Fsection;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class SectionTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sectionTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探赛季、赛事阶段、分组、轮次数据';
    public static $Url = 'http://interface.win007.com/football/subLeague.aspx';

    public function handle () {
        $sectionRes = self::send_request(self::$Url);
        $sectionMap = json_decode($sectionRes['content']);
        foreach ($sectionMap->list as $key => $val) {
            // 获取联赛ID
            $leagueData = Fleague::where('out_league_id', $val->leagueId)->first();
            $leagueData = $leagueData ? $leagueData->toArray() : [];
            $leagueId = $leagueData['league_id'];
            $leagueType = $leagueData['league_type'];

            if (isset($val->subId)) {
                Fleague::where('out_league_id', $val->leagueId)->update(['sub_id' => $val->subId]);
            }
            if (!$leagueId) {
                continue;
            }
            $seasonWhere = [
                'league_id' => $leagueId,
                'out_league_id' => $val->leagueId,
                'season_name' => $val->currentSeason
            ];
            $seasonData = [
                'league_id' => $leagueId,
                'out_league_id' => $val->leagueId,
                'season_name' => $val->currentSeason
            ];
            $seasonId = Fseason::handleSeason($seasonData);
            // 处理赛事阶段数据
            $sectionWhere = [
                'season_id' => $seasonId,
                'section_name' => $val->subNameChs
            ];
            $sectionData = [
                'out_section_id' => $val->subId,
                'season_id' => $seasonId,
                'section_name' => $val->subNameChs,
                'section_name_hk' => $val->subNameCht,
            ];
            Fsection::handleSection($sectionWhere, $sectionData);

        }
        $this->info('共处理'.count($sectionMap->list).'条数据');
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