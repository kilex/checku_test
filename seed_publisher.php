<?php

require_once __DIR__.'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Dotenv\Dotenv;

class SeedPublisher
{
        private $connection;
        private $channel;
        private $exchangeName;

        public function __construct(string $exchangeName = "seed_data")
        {
            $this->exchangeName = $exchangeName;
        }

        public function connect(AMQPStreamConnection $connection) {
            $this->connection = $connection;
            $this->channel = $connection->channel();
            $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
        }

        public function close() {
            $this->channel->close();
            $this->connection->close();
        }

        public function publish($msgData, $to) {
            $msgData['cashN'] = $to;       
            $messageBody = json_encode($msgData);
            $msgProps = [
                'content_type' => 'application/json', 
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ];
            $message = new AMQPMessage($messageBody, $msgProps);
            $this->channel->basic_publish($message, $this->exchangeName);
        }
}

class SeedDataProvider {
    
    public function call(\DateTime $startDate, int $days = 365) {
        $data = [];
        $day = 0;
        $checkNum = 1;
        while ($day < $days) {
            foreach ($this->generateChecks(clone $startDate, $checkNum) as $check) {
                yield $check;
            }
            $startDate->modify('+1 day');
            $day = $day + 1;
        }
    }

    private function generateChecks(\DateTime $date, int &$num) {
        $count = rand(400, 800);
        for ($i = 0; $i < $count; $i++) {
            $date->setTime(
                rand(6, 21),
                rand(0, 59),
                rand(0, 59)
            );
            yield [
                "number" => $num,
                "tz" => $date->getOffset(),
                "timestamp" => $date->getTimestamp(),
                "iso8601" => $date->format('c'),
                "amount" => rand(80, 150),
            ];
            $num = $num + 1;
        }
    }
}

$dotenv = new Dotenv(__DIR__);
$dotenv->load();

$connection = new AMQPStreamConnection(
    env('RMQ_HOST'), 
    env('RMQ_PORT'), 
    env('RMQ_USER'), 
    env('RMQ_PASS'), 
    env('RMQ_VHOST')
);
$publusher = new SeedPublisher();
$publusher->connect($connection);

$dataProvider = new SeedDataProvider();
$timezones = [
    1 => new DateTimeZone("-08:00"),
    new DateTimeZone("+00:00"),
    new DateTimeZone("+08:00"),
];

echo "start seed publish\n";

$days = $argv[1] ?? 70;
echo("seed days = $days\n");
foreach ($timezones as $cash => $tz) {
    $startDate = new \DateTime('now', $tz);
    $startDate->modify('midnight');
    foreach ($dataProvider->call($startDate, 60) as $checkData) {
        $publusher->publish($checkData, $cash);
    }
}

$publusher->close();
echo "finish\n";