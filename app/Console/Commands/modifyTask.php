<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Fevent;
use App\models\Fmatch;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class modifyTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:modify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探足球比赛删除修改数据';
    public static $modify_url = 'http://interface.win007.com/football/ModifyRecord.aspx';
    public static $source = 'win007';

    public function handle () {
        $res = self::send_request(self::$modify_url);
        $data = json_decode($res['content']);

        $match_data = $data->changeList;

        $matches = [];

        if ( count($match_data) > 0 ) {
            foreach ($match_data as $key => $val) {
                $out_matchid = $val->matchId;
                $matches[$out_matchid]['type'] = $val->type;
                $matches[$out_matchid]['matchtime'] = $val->matchTime;
                $matches[$out_matchid]['oprTime'] = $val->oprTime;
            }

            $out_matchid_map = array_keys($matches);

            $matchid_map = [];

            $match_arr = Fmatch::whereIn('out_match_id', $out_matchid_map)->get(['match_id', 'out_match_id']);

            $match_arr && $matchid_map = array_column( $match_arr->toArray(), 'match_id', 'out_match_id' );

            foreach ( $matches as $key => $val ) {
                if ( $val['type'] == 'modify' ) {
                    Fmatch::where('out_match_id', $key)->update(['match_time' => $val['matchtime']]);
                    Fevent::where('out_match_id', $key)->update(['start_time' => $val['matchtime']]);
                } else if ($val['type'] == 'delete') {
                    Fmatch::where('out_match_id', $key)->delete();
                    Fevent::where('out_match_id', $key)->delete();
                }
            }
        }

        $this->info('本次处理'.count($match_data).'条数据');
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