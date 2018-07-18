<?php
/**
 * Created by PhpStorm.
 * User: tu6ge
 * Date: 2018/7/17
 * Time: 10:17
 */
namespace app\http;

use think\swoole\Server;

class Http extends Server
{
    protected $host = '0.0.0.0';
    protected $port = 1234;
    protected $serverType = 'http';
    protected $option = [
        'worker_num'=> 4,
        'daemonize'	=> false,
        'backlog'	=> 128
    ];

    public function onStart($server)
    {
        echo "Swoole http server is started at http://0.0.0.0:1234\n";
    }

    public function onRequest($request, $response)
    {
        $response->header("Content-Type", "text/plain");
        $response->end("Hello World\n");

    }

    public function onReceive($server, $fd, $from_id, $data)
    {
        $server->send($fd, 'Swoole: '.$data);
    }
//    public function onOpen($server, $request) {
//        echo "server: handshake success with fd{$request->fd}\n";
//        $server->push($request->fd, " on open");
//    }
//    public function onMessage($server, $frame) {
//        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
//        $server->push($frame->fd, "this is server");
//    }
    public function onClose($server, $fd) {
        echo "client {$fd} closed\n";
    }
}