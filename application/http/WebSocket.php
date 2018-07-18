<?php
/**
 * Created by PhpStorm.
 * User: tu6ge
 * Date: 2018/7/17
 * Time: 10:17
 */
namespace app\http;

use think\Config;
use think\swoole\Server;

class WebSocket extends Server
{
    protected $host = '0.0.0.0';
    protected $port = 9501;
    protected $serverType = 'socket';
    private $table ;
    private $table_online;
    private $config = [];
    protected $option = [
        'worker_num'=> 4,
        'daemonize'	=> false,
        'backlog'	=> 128
    ];
    public function init()
    {
        $this->createTable();
        $this->createTableOnline();
        $this->config = \think\facade\Config::get('temp_profile');
        echo 'init succ'."\n";
    }

    public function onOpen($server, $request) {
        $user = [
            'fd' => $request->fd,
            'name' => $this->config['name'][array_rand($this->config['name'])].$request->fd,
            'avatar' => $this->config['avatar'][array_rand($this->config['avatar'])]
        ];
        $this->add_online($user);
        $server->push($request->fd, json_encode(
                array_merge(['user' => $user], ['all' => $this->allUser()], ['type' => 'openSuccess'])
            )
        );
        $this->pushMessage($server, "欢迎".$user['name']."进入聊天室", 'open', $request->fd);
    }
    public function onMessage($server, $frame) {
        $this->pushMessage($server, $frame->data, 'message', $frame->fd);
    }
    public function onClose($server, $fd) {
        $user = $this->table->get($fd);
        $this->pushMessage($server, $user['name']."离开聊天室", 'close', $fd);
        $this->del_online($fd);
    }
    /**
     * 遍历发送消息
     *
     * @param Server $server
     * @param $message
     * @param $messageType
     * @param int $skip
     */
    private function pushMessage(\Swoole\Server $server, $message, $messageType, $frameFd)
    {
        $message = htmlspecialchars($message);
        if($this->debug_message($message,$frameFd)){
            return;
        }
        $datetime = date('Y-m-d H:i:s', time());
        $user = $this->table->get($frameFd);
        $all_user = $this->allUser();
        foreach ($all_user as $row) {
            if ($frameFd == $row['fd']) {
                continue;
            }
            $server->push($row['fd'], json_encode([
                    'type' => $messageType,
                    'message' => $message,
                    'datetime' => $datetime,
                    'user' => $user
                ])
            );
        }
    }
    public function debug_message($message){
        if(in_array($message,['all']))
        return true;
    }

    private function allUser()
    {
        $list = $this->table_online->get(1,'list');
        $list = explode(',',$list);
        $list = array_filter($list);
        $users = [];
        foreach ($list as $key=>$val) {
            $users[] = $this->table->get($key);
        }
        return $users;
    }

    private function add_online($user)
    {
        $list = $this->table_online->get(1,'list');
        $ids = explode(',',$list);
        if(!isset($ids[$user['fd']])){
            $ids[$user['fd']] = 1;
            $this->table->set($user['fd'], $user);
        }
        $this->table_online->set(1,['list'=>implode(',',$ids)]);
    }
    private function del_online($fd)
    {
        $list = $this->table_online->get(1,'list');
        $ids = explode(',',$list);
        if(!isset($ids[$fd])){
            unset($ids[$fd]);
            $this->table->del($fd);
        }
        $this->table_online->set(1,['list'=>implode(',',$ids)]);
    }

    /**
     * 创建内存表
     */
    private function createTable()
    {
        $this->table = new \swoole_table(1024);
        $this->table->column('fd', \swoole_table::TYPE_INT);
        $this->table->column('name', \swoole_table::TYPE_STRING, 255);
        $this->table->column('avatar', \swoole_table::TYPE_STRING, 255);
        $rs = $this->table->create();
        if($rs===false){
            echo 'table create fail'."\n";
        }
    }
    private function createTableOnline()
    {
        $this->table_online = new \swoole_table(1024);
        $this->table_online->column('list', \swoole_table::TYPE_STRING, 1024);
        $rs = $this->table_online->create();
        if($rs===false){
            echo 'table create fail'."\n";
        }
        $this->table_online->set(1,['list'=>'']);
    }
}