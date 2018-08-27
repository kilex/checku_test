<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Cashbox;
use App\SeedConsumer as Consumer;

class SeedConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read and store data from RMQ';

    private $consumer;

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
        Schema::drop("cashboxes");
        Schema::drop("checks");
        if (!Schema::hasCollection("cashboxes")) {
            Schema::create("cashboxes", function($collection){
                $collection->index('timestamp');
            });

            $date = new \DateTime('now', new \DateTimeZone("+00:00"));
            $timezones = [
                1 => new \DateTimeZone("-08:00"),
                new \DateTimeZone("+00:00"),
                new \DateTimeZone("+08:00"),
            ];

            foreach ($timezones as $n => $tz) {
                $cashBox = new Cashbox;
                $cashBox->number = $n;
                $cashBox->tz_offset = $tz->getOffset($date);
                $cashBox->save();
            }
        }
        
        $connection = new AMQPStreamConnection(
            env('RMQ_HOST'), 
            env('RMQ_PORT'),
            env('RMQ_USER'), 
            env('RMQ_PASS'), 
            env('RMQ_VHOST')
        );
        $this->consumer = new Consumer();
        $this->consumer->connect($connection);
        
        // pcntl_signal(SIGINT, [$this, 'shutdown']);
        // pcntl_signal(SIGTERM, [$this, 'shutdown']);
        
        $this->consumer->work();
    }

    public function shutdown()
    {
        $this->consumer->close();
    }
}
