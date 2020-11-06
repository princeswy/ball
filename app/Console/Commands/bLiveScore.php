<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use swoole_websocket_server;
use swoole_process;
use swoole_redis;
use swoole_table;
use Predis;
use App\models\Bevent;


class bLiveScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:ball';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '篮球比分直播socket';

    protected $server_ip;

    protected $task_key = 'SWTASK:%s:%s';

    protected $chat_channel = 'SWTASKCH-%s:%s';

    protected $channel_key = [
        'score' => 'push_bball',
    ];

    protected $match_odds_key = "BMATCH:OODS:REDIS:KEY:%s";


    public static $regex = "/\s|\/|\~|\!|\@|\#|\\$|\%|\^|\&|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";

    public static $gag_long_map = [ 1 => 600, 2 => 1800, 3 => 3600, 4 => 86400, 5 => 604800, 6 => 2592000 ];



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->server_ip = env('SERVER_IP','0.0.0.0');
        $this->last_ip = substr($this->server_ip, strrpos($this->server_ip, '.') +1 );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ('cli' == php_sapi_name()) {

            $port = 9601;
            $this->server = new swoole_websocket_server($this->server_ip, $port);
            $this->server->set([
                'worker_num' => 4,
                'task_worker_num' => 2,
                'buffer_output_size' => 4 * 1024 * 1024,
//                'log_file' => '/var/log/swoole_bball.log',
                'log_file' => '/Users/songwy/log/swoole_bball.log',
                'dispatch_mode' => 1,
                'daemonize' => 0,
                'heartbeat_check_interval' => 60,
            ]);
        }
        //

        $this->server->on('open', function (swoole_websocket_server $server, $request) {
            // echo "server: handshake success with fd{$request->fd}\n";
        });

        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
            //echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, json_encode(["this is server,welcome to you"]));
        });

        $this->server->on('Start',[$this,'onStart']);
        $this->server->on('ManagerStart',[$this,'onManagerStart']);
        $this->server->on('WorkerStart',[$this,'onWorkerStart']);
        $this->server->on('Task',[$this,'onTask']);
        $this->server->on('Finish',[$this,'onFinish']);
        $this->server->start();
    }

    public function onStart(){
//        swoole_set_process_name("swoole_master");
    }

    public function onManagerStart(){
//        swoole_set_process_name("swoole_manager");
    }

    public function onWorkerStart(swoole_websocket_server $server)
    {
//        swoole_set_process_name("swoole_worker");
        if (!$server->taskworker) {
            $server->tick(1000, function () use ($server) {
                if(!$server->worker_id){
                    $server->task('push');
                }
            });
        }
    }

    public function onTask(swoole_websocket_server $server, $task_id, $from_id,$data)
    {
        if(!$from_id){
            $data = Bevent::get_score();
            if($data && $server->connections){
                foreach($server->connections as $fd) {
                    if($server->connection_info($fd)['websocket_status'] == 3){
                        $server->push($fd, $data);
                    }
                }
            }

        }
        $server->finish('end');
    }

    public function onFinish(swoole_websocket_server $server, $task_id, $reponse)
    {
// 	    echo "Task: {$task_id} success {$reponse}\n";
    }


}
