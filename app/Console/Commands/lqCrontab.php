<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Bleague;
use App\models\Bmatch;
use App\models\Bodds;
use App\models\Bscoretable;
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

    static $model = [
        '3W' => 'App\models\Bodds',
        'HC' => 'App\models\Bhcodds',
//        'asian_total' => 'App\models\Basiantotal',
        'asian_total' => 'App\models\Btotalhandicap',
    ];

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
                $this->warn('sleep 90s');
                sleep(90);
            }
        }

    }

    public function Handle_Odds(){

        $a = microtime(true);
        set_time_limit(0);

        $type = $this->option('odds_type');
        $res = self::send_request(self::$odds_url);
        $out_data = json_decode($res['content']);
        if (!isset($out_data->List) || !isset($out_data->List[0]->spread)) {
            return false;
        }
        $out_data = $out_data->List[0];

        $hc_mes = $out_data->spread;
        $odds_mes = $out_data->moneyLine;
        $asian_totalmes = $out_data->total;

        $type_map = [ '3W' => $odds_mes , 'HC' => $hc_mes, 'asian_total' => $asian_totalmes ];

        $out_oddsdata = call_user_func_array( [ self::$model[$type], 'convert_qtOdds' ], [$type_map[$type]] );

        if($out_oddsdata) foreach ($out_oddsdata as $val){
            $ret = call_user_func_array( [ self::$model[$type], 'compareInsert' ], [$val['start'], $val['end']] );

            !$ret || $match_ids[] = $val['start']['match_id'];
        }

        $b = microtime(true);echo $b-$a."<br>";

    }

    public function Handle_3WOdds(){

        $url =
        $source = 'win007';
        $a = microtime(true);
        set_time_limit(0);

        $script_name = $this->signature.' --type='.$this->option('type');
        check_process_num($script_name) || exit('Process limit');

        $time = $this->option('time');
        $type = $time < 5 ? '?day='.$time : '?min='.$time;

        $url = self::$odds_3w_url.$type;

        echo $url.$type."\n";

        $res = self::send_request($url);
        $out_data = json_decode($res['content']);
        $b = microtime(true);
        echo $b-$a."<br>";
        $odds = Bodds::convert_qtOdds_old($out_data);
        $match_ids = [];

        if($odds) foreach ($odds as $val){

            $ret = Bodds::compareInsert($val['start'],$val['end']);

//            dd($val);
            Bodds::update_oddscache($val['start']['match_id']);

//            !$ret || $match_ids[] = $val['start']['match_id'];
        }


        $b = microtime(true);echo $b-$a."<br>";

    }

    public function Handle_ScoreTable(){

        $a = microtime(true);

        $league_id = $this->option('league_id');
        $season_name = $this->option('season_name');

        if(!$league_id){
            $this->info('!league_id');
            return false;
        }

        $out_league_mes = Bleague::where( [ 'league_id' => $league_id ] )->first();
        if ( !$out_league_mes ){
            $this->info('联赛未匹配');
            return false;
        }

        $url = sprintf( self::$scoretable_url, $out_league_mes->out_league_id );

        if ( $season_name ) {
            $url = $url.'&season='.$season_name;
        }
        echo $url."\n";

        $res = self::send_request($url);
        $out_data = json_decode($res['content']);
        $datas = Bscoretable::convert_qt($out_data->list, $league_id);

        if ( $datas ) {
            foreach ( $datas as $key => $val ) {

                $where = [
                    'season_id' => $val['season_id'],
                    'team_id' => $val['team_id'],
                ];

                Bscoretable::updateOrCreate( $where, $val );
            }
        }

        echo microtime(true) - $a;

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