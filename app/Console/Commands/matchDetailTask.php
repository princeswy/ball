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
use App\models\Feventdetail;
use App\models\Fgym;
use App\models\Fleague;
use App\models\Fmanager;
use App\models\Fmatch;
use App\models\Fmissplayer;
use App\models\Fmstatis;
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

class matchDetailTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:matchDetail {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探比赛技术统计过数据';
    public static $Url = 'http://interface.win007.com/football/detail.aspx';
    public static $source = 'win007';

    public function handle () {
        $url = self::$Url;
        $date = $this->option('date');
        $script_name = $this->signature;
        if ($date) {
            $url = $url.'?date='.$date;
            $script_name = $script_name.' -date='.$date;
        }
        self::check_process_num($script_name) || exit('Process limit');
        $res = self::send_request(self::$Url);
        $resData = json_decode($res['content']);
        $detailData = Feventdetail::handleData($resData);
        $statisData = Fmstatis::handleData($resData);
        foreach ($detailData as $k => $v) {
            Feventdetail::where('match_id', $k)->delete();
            foreach ($v as $key => $val) {
                $where = [
                    'out_match_id' => $val['out_match_id'],
                    'happen_time' => $val['happen_time'],
                    'type' => $val['type'],
                ];

                Feventdetail::updateOrCreate($where, $val);
            }
        }
        foreach ($statisData as $k => $v) {
            $where = [
                'out_match_id' => $v['out_match_id']
            ];
            Fmstatis::updateOrCreate($where, $v);
        }
//        $this->info('共处理'.count($resData->list).'场比赛数据');
    }

    public static function check_process_num($script_name) {
        $cmd = @popen("ps -ef | grep '{$script_name}' | grep -v grep | wc -l", 'r');
        $num = (int) @fread($cmd, 512);
        (int) $num += 0;
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