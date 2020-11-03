<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Bmatch;
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
use GuzzleHttp\Pool;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use MongoDB\Driver\Manager;

class lqCrontab extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lq:crontab {--type=} {--odds_type=} {--time=} {--league_id=} {--season_name=} {--date=} {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探篮球比赛、赔率、积分榜、比分数据';
    public static $match_url = 'http://interface.win007.com/basketball/schedule.aspx';

    public static $odds_url = 'http://interface.win007.com/basketball/odds.aspx';

    public static $odds_3w_url = 'http://interface.win007.com/basketball/1x2.aspx';

    public static $scoretable_url = 'http://interface.win007.com/basketball/rankings.aspx?leagueId=%s';

    public static $modify_match_url = 'http://interface.win007.com/basketball/modifyRecord.aspx';
    public static $source = 'win007';

    public function handle () {
        $type = $this->option('type');

        if ( !$type ) {
            $this->info('!type');
            return false;
        }

        switch ( $type ) {

            case 'match' :
                $this->Handle_Match();
                break;
            case 'odds' :
                $this->Handle_Odds();
                break;
            case '3w_odds' :
                $this->Handle_3WOdds();
                break;
            case 'score_table' :
                $this->Handle_ScoreTable();
                break;
            case 'modify_match' :
                $this->Handle_ModifyMatch();
                break;
            case 'today_score' :
                $this->Handle_score();
                break;

        }
    }

    public function Handle_Match(){

        $date = $this->option ( 'date' ) ? $this->option ( 'date' ) : date('Y-m-d');
        $days = $this->option ( 'days' ) ? intval ( $this->option ( 'days' ) ) : 5;
        $league_id = $this->option('league_id');

        $sourcedata = [];
        if ( $league_id ) {
            $url = self::$match_url.'?leagueId='.$league_id;
            $res = self::send_request($url);
            $sourcedata = json_decode($res['content']);
            $this->info('共'.count($sourcedata->matchList).'条数据');
            $data = Bmatch::convert_qtMatch($sourcedata);

            if ($data) foreach ($data as $key=>&$val) {

                vv($val['out_match_id']);
                $where = ['out_match_id' => $val['out_match_id'], 'source' => $val['source']];

                Bmatch::updateOrCreate($where, $val);

            }
        }
        else if ( $date && $days ) {
            for ($i = 0; $i <= $days; $i++) {
                $date = date("Y-m-d", strtotime("+$i day", strtotime($date)));
                $url = self::$match_url."?date=$date";
                $res = self::send_request($url);
                $sourcedata = json_decode($res['content']);
                $this->info('共'.count($sourcedata->matchList).'条数据');
                $data = Bmatch::convert_qtMatch($sourcedata);

                if ($data) foreach ($data as $key=>&$val) {

                    vv($val['out_match_id']);
                    $where = ['out_match_id' => $val['out_match_id'], 'source' => $val['source']];

                    Bmatch::updateOrCreate($where, $val);

                }
                sleep(90);
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