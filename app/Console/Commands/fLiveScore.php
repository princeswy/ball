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

class fLiveScore extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:liveScore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取球探即时比分数据';

    public function handle () {
        for ( $i = 1; $i <= 20; $i ++ ) {
            $this->live_score();
            $this->info('sleep 5');
            sleep(3);
        }
    }

    public function live_score() {
        $a = microtime(true);
        $source = 'win007';
        $url1 = "http://interface.win007.com/zq/change.xml";
        $url2 = "http://interface.win007.com/zq/change2.xml";

        $client = new Client([
            'timeout' => 10,
        ]);

        $reponse1 = $client->get($url1);

        $res1 = (string) $reponse1->getBody();

        $reponse2 = $client->get($url2);

        $res2 = (string) $reponse2->getBody();

        if ( !$res1 ) {
            return false;
        }
/*        $res1 = "<?xml  version=\"1.0\" encoding=\"gb2312\"?><c refresh='0'><h><![CDATA[1880507^2^0^5^0^5^0^0^11:00^2020,9,30,11,00,00^^1^2^0^10-30^^4^2^1]]></h></c>";*/
/*        $res2 = "<?xml  version=\"1.0\" encoding=\"gb2312\"?><c refresh='0'><h><![CDATA[1880507^2^0^5^0^5^0^0^11:00^2020,9,30,11,00,00^^1^2^0^10-30^^4^2^1]]></h></c>";*/

        $score_qt0 = Fevent::convert_qtscore_new($res1);
        $score_qt1 = Fevent::convert_qtscore_new($res2);

        $score_qt = $score_qt0 + $score_qt1;
//        $score_qt = $score_qt0;

        $out_matchids = array_keys($score_qt);

        $matchid_map_out = Fmatch::whereIn( 'out_match_id', $out_matchids )->get(['match_id', 'out_match_id']);

//        $match_id_map = array_filter(Fmatchmap::get_matchid_redis($out_matchids, $source));

        if ( !$matchid_map_out ) {
            $this->info('no matching match');
            return false;
        }
        $matchid_map_out = $matchid_map_out->toArray();
        $match_id_map = array_column( $matchid_map_out, 'match_id', 'out_match_id' );

//        $status_map = [ 4, 5, 6, 10, 12, 13, 14, 17 ];
        $status_map = [ 0 ];
        foreach ( $score_qt as $key => $val ) {
            $out_matchid = $key;
            if ( in_array($val['ss'], $status_map) ) {
                $this->info('this match is over or not open');
                continue;
            }
            $val['match_id'] = isset($match_id_map[$out_matchid]) ? $match_id_map[$out_matchid] : 0;
            Fevent::updateData($val);
//            sleep(1);
        }

        $this->info("本次处理".count($match_id_map).'场比赛,耗时: '.(microtime(true) - $a));
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