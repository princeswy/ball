<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\bookmaker;
use App\models\Fhandicap;
use App\models\Freferee;
use App\models\Ftotalodds;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class hOddsTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:hOddsTask {--odds_type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探亚盘数据';
    public static $hand_odds_url = 'http://interface.win007.com/football/odds.aspx';
    public static $change_odds_url = 'http://interface.win007.com/football/oddsChange.aspx';

    public function handle () {
        $a = microtime(true);
        set_time_limit(0);
        $type = $this->option('odds_type');
        $script_name = $this->signature.' --odds_type='.$type;
        self::check_process_num($script_name) || exit('Process limit');
        $res = $this->send_request(self::$hand_odds_url);
        $out_data = json_decode($res['content']);
        $out_data = $out_data->list[0];
        if($type == 1){
            $this->grab_odds($out_data);
            return true;
        }
        if ($type == 2) {
            $this->total_odds($out_data);
            return true;
        }

        while(true )
        {
            $res = $this->send_request(self::$hand_odds_url);
            $out_data = json_decode($res['content']);
            $out_data = $out_data->changeList[0];
            $book_data = bookmaker::where(['type' => 2])->get(['bookmaker_id','out_bookmaker_id'])->toArray();
            $bookid_maps = array_column($book_data, 'bookmaker_id','out_bookmaker_id');
            $odds = Fhandicap::handle_QtLiveodds($out_data->handicap, $bookid_maps);//[0]
            $match_ids = [];

            if($odds) foreach ($odds as $val){
                $ret = Fhandicap::compareInsert($val);
                !$ret || $match_ids[] = $val['match_id'];
                $match_ids[] = $val['match_id'];
            }
            sleep(20);
        }
    }

    private function grab_odds($out_data)
    {
        $a = microtime(true);
        echo "<pre>";

        #亚盘
        $odds = Fhandicap::handle_QtOdds($out_data);
        #亚盘
        if($odds) foreach ($odds as $val){
            $ret = Fhandicap::compareInsert($val['start'],$val['end']);
            !$ret || $match_ids[] = $val['start']['match_id'];
        }

        $b = microtime(true);
        $this->info($b-$a);
    }

    private function total_odds($out_data)
    {
        $a = microtime(true);
        echo "<pre>";

        #大小盘
        $total_odds = Ftotalodds::handle_QtOdds($out_data);

        #大小盘
        if($total_odds) foreach ($total_odds as $val){
            $total_ret = Ftotalodds::compareInsert($val['start'],$val['end']);
            !$total_ret || $total_match_ids[] = $val['start']['match_id'];
        }

        $b = microtime(true);
        $this->info($b-$a);
        $this->info(count($out_data->overUnder));
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
    private function send_request($Url, $Method = 'GET', $Parameter = [], $Header = []) {

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