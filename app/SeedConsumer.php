<?php

namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Message\AMQPChannel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Check;
use App\Cashbox;

class SeedConsumer
{
    private $connection;
    private $channel;
    private $exchange;
    private $queue;

    private $logger;

    public function __construct()
    {
        $logger = new Logger('seed_consumer');
        $logger->pushHandler(new StreamHandler("php://stdout", Logger::DEBUG));
        
        $this->logger = $logger;
        $this->exchange = "seed_data";
    }

    public function connect(AMQPStreamConnection $connection) {
        $this->connection = $connection;
        $this->channel = $connection->channel();
        list($this->queue,) = $this->channel->queue_declare("", false, false, false, true);
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->basic_qos(null, 10, null);
        $this->channel->queue_bind($this->queue, $this->exchange);
    }

    public function close() {
        $this->logger->warn("close consumer");
        $this->channel->close();
        $this->connection->close();
    }

    public function work() {
        $this->logger->debug("start work");
        $cashBoxes = [];
        foreach (Cashbox::all() as $cashbox) {
            $cashBoxes[$cashbox->number] = $cashbox->_id;
        }

        $this->channel->basic_consume($this->queue, '', false, true, true, false, 
            function($msg) use ($cashBoxes) 
            {
                $attrs = \json_decode($msg->body, true); 
                
                $utcTZ = new \DateTimeZone("UTC");
                if (isset($attrs['cashN'])) {
                    $check = new Check();

                    $datetime = \DateTime::createFromFormat("U", $attrs["timestamp"], $utcTZ);
                    $check->amount = intval($attrs["amount"]);
                    $check->timestamp = new \MongoDB\BSON\UTCDateTime($datetime);
                    $check->number = intval($attrs["number"]);
                    $check->cashbox = $cashBoxes[$attrs['cashN']];

                    $check->save();
                    $this->logger->debug("save check", $check->getAttributes());
                }
        });
        
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->close();
    }
}