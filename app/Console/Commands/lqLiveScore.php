<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\models\Bevent;
use App\models\Bmatch;
use App\models\Bseason;
use Illuminate\Support\Facades\Artisan;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class lqLiveScore extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'grab:event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取篮球即时比分';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        for ( $i = 1; $i <= 20; $i ++ ) {
            $this->live_score();
            $this->info('sleep 5');
            sleep(3);
        }

    }
    public function live_score(){
        $a = microtime(true);
        $source = 'win007';
        $url = "http://interface.win007.com/basketball/change.aspx?language=cn";
        $res = self::send_request($url);
        $data = json_decode($res['content']);


        if ( !$data || !isset($data->changeList) || empty($data->changeList) ) {
            return false;
        }

        $up_match = [];

        $score_qt = Bevent::convert_qtscore( $data,  $up_match );

        if ( !$score_qt ) {
            return false;
        }

        $match_ids = array_column($score_qt, 'match_id');
        $score_redis = Bevent::get_score_redis($match_ids);

        $up_score = [];

        if($score_qt) foreach ($score_qt as $key=>$val){

            if(!isset($score_redis[$key]) || !$score_redis[$key] || $score_redis[$key] != json_encode($val)){

                Bevent::updateOrCreate(['match_id' => $key,],$val);
                $up_score[$key] = json_encode($val);
                var_dump($val);
                Bevent::write_bscore($val);
            }
            Artisan::call( 'lq:lqStatistics', [ '--date' => date('Y-m-d H:i:s') ] );
        }

        $up_score && $aa = Bevent::set_score_redis($up_score);

        if($up_match){
            foreach ($up_match as $key=>$val){
                $ret[$key] = Bmatch::up_match_finish($val);
            }
        }

        #获取球探联赛积分榜数据
        $season_id = Bmatch::whereIn('id', $match_ids)->get(['season_id'])->toArray();
        $season_ids = array_column($season_id, 'season_id');

        $season_map = Bseason::whereIn('id', $season_ids)->get(['league_id', 'season_name'])->toArray();
        $league_map = array_column($season_map, 'season_name', 'league_id');

        foreach ($league_map as $key => $val) {
            $season_name = $val;
            if ( strstr($val, '-') ) {
                list($first, $last) = explode('-', $val);
                if ( strlen($first) == 4 ) {
                    $first = substr($first, -2);
                }
                if ( strlen($last) == 4 ) {
                    $last = substr($last, -2);
                }
                $season_name = $first.'-'.$last;
            }
            if ( strlen($val) == 4 ) {
                $season_name = substr($val, -2);
            }

            Artisan::call( 'lq:crontab', [ '--type' => 'score_table', '--league_id' => $key, '--season_name' => $season_name ] );

        }

        $this->info("本次处理".count($score_qt).'场比赛,耗时: '.(microtime(true) - $a));

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
