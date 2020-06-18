<?php
header("Content-Type:text/html; charset=utf-8");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);
error_reporting(E_ALL);


class swooleWs
{
    private $ws;

    function __construct()
    {
        $this->ws = new Swoole\WebSocket\Server("0.0.0.0", 3000);

        $this->ws->on('WorkerStart', function ($ws, $worker_id) {
            if ($worker_id == 0) {
                $this->ws->tick(1000 * 60, [$this, 'tick']);
            }
        });


        $this->ws->on('open', function ($ws, $request) {
            // var_dump($request->fd, $request->get, $request->server);
            $this->ws->push($request->fd, "sys:{$request->fd}");
            $this->ws->push($request->fd, "👋 hello, welcome No. {$request->fd}\n");
        });

        $this->ws->on('message', function ($ws, $frame) {

            echo "Message: {$frame->data}\n";
            list($cmd, $msg) = explode(',', $frame->data);
            var_dump($cmd, $msg);

            switch ($cmd) {
                case 'cmd:btnWhich':

                    foreach ($this->ws->connections as $fd) {
                        $fd_ids[] = $fd;
                    }
                    $this->ws->push($frame->fd, json_encode($fd_ids));
                    break;

                default:
                    $this->toAll($msg, $frame->fd);
                    break;
            }
        });

        $this->ws->on('close', function ($ws, $fd) {
            $msg = "❌ client-{$fd} is closed\n";
            echo $msg;
            $this->toAll($msg);
        });

        $this->ws->start();
    }

    public function tick()
    {
        $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
        $this->toAll("time is: {$now->format('Y-m-d h:i:s')}");
    }

    private function toAll($msg, $sender_fd = null)
    {
        $say = '';
        if ($sender_fd) {
            $say = "{$sender_fd} say: ";
        }

        $msg = $say . $msg;
        foreach ($this->ws->connections as $fd) {
            $this->ws->push($fd, $msg);
        }
    }
}

$swooleWs = new swooleWs();
