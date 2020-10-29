<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\bookmaker;
use App\models\Continent;
use App\models\Country;
use App\models\Fleague;
use App\models\Fmatch;
use App\models\Fodds;
use App\models\Freferee;
use App\models\Fseason;
use App\models\Fsection;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class OddsTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:oddsTask {--time=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探欧赔数据';
    public static $Url = 'http://interface.win007.com/football/1x2.aspx';

    public function handle () {
        $time = $this->option('time');
        $type = $time < 5 ? '?day='.$time : '?min='.$time;
        $url = self::$Url.$type;
        echo $url;
        $res = self::send_request($url);
        $resData = json_decode($res['content']);
        if (isset($resData->list) && count($resData->list) > 0) {
            $odds = [];
            foreach ($resData->list as $key => $val) {
                // 获取比赛ID
                $matchData = Fmatch::where('out_match_id', $val->matchId)->first();
                $matchId = $matchData ? $matchData->toArray()['match_id'] : 0;
                $now_time = date('Y-m-d H:i:s');
                if (isset($val->oddsList) && count($val->oddsList) > 0) {
                    foreach ($val->oddsList as $odds_key => $odds_val) {
                        $oddsMap = explode(',', $odds_val->odds);
                        $out_bookmaker_id = $oddsMap[0];
                        $bookmaker_name = $oddsMap[1];
                        $s_win = $oddsMap[2];
                        $s_draw = $oddsMap[3];
                        $s_lost = $oddsMap[4];
                        $win = $oddsMap[5];
                        $draw = $oddsMap[6];
                        $lost = $oddsMap[7];
                        // 博彩公司
                        $bookmakerData = [
                            'out_bookmaker_id' => $out_bookmaker_id,
                            'bookmaner_name' => $bookmaker_name,
                            'type' => 1
                        ];
                        $bookmakerId = bookmaker::handleData($bookmakerData, $bookmakerData);

                        $up_time =  date('Y-m-d H:i:s',strtotime($odds_val->changeTime));
                        $odds[] = [
                            'start' => [
                                'match_id' => $matchId,
                                'out_match_id' => $val->matchId,
                                'bookmaker_id' => $bookmakerId,
                                'odds_type' => 0,
                                'win' => $s_win,
                                'draw' => $s_draw,
                                'lost' => $s_lost,
                                'add_time' => $now_time,
                                'update_time' => $win ? date('Y-m-d H:i:s',strtotime($up_time) - 60) : $up_time,
                            ],
                            'end' => [
                                'match_id' => $matchId,
                                'out_match_id' => $val->matchId,
                                'bookmaker_id' => $bookmakerId,
                                'odds_type' => 1,
                                'win' => $win,
                                'draw' => $draw,
                                'lost' => $lost,
                                'add_time' => $now_time,
                                'update_time' => $up_time,
                            ],
                        ];
                    }
                }
            }

            if($odds) foreach ($odds as $val){
                $ret = Fodds::compareInsert($val['start'], $val['end']);
                !$ret || $match_ids[] = $val['start']['match_id'];
            }
        }
        $this->info('共处理'.count($resData->list).'条数据');
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