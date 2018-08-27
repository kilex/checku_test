<?php

namespace App;

use App\Check;
use MongoDB\BSON\UTCDateTime as MongoDateTime;

class StatsCheck
{
    public function byMonth($startDate, $endDate) {
        return $this->aggregateByPeriod('$month', $startDate, $endDate);
    }

    public function byDaysOfMonth($startDate, $endDate) {
        return $this->aggregateByPeriod('$dayOfMonth', $startDate, $endDate);
    }

    private function aggregateByPeriod($period, $startDate, $endDate) {
        return Check::raw(function($collection) use ($period, $startDate, $endDate){
            return $collection->aggregate([
                [
                    '$match' => [
                        'timestamp' => [
                            '$gte' => new MongoDateTime($startDate), 
                            '$lt' => new MongoDateTime($endDate),
                        ]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'cashbox' => '$cashbox',
                            'date' => [$period => '$timestamp'],
                        ], 
                        'totalAmount' => ['$sum'  => '$amount'],
                        'count' => ['$sum' => 1]
                    ],
                ],
                [
                    '$sort' => ['_id.cashbox' => 1, '_id.date' => 1]
                ],
            ]);;
        });
    }
}