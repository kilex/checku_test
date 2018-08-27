<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Check;
use MongoDB\BSON\UTCDateTime as MongoDateTime;
use App\StatsCheck;

class CheckController extends Controller
{
    public function year_stats(Request $request)
    {
        $year = $request->query('year', null);
        $now = $this->timeNow();
        
        if (is_null($year)) {
            $year = $now->format('o');
        }

        $startDate = $now->setDate($year, 1, 1)->modify('midnight');
        $endDate = $startDate->modify('+1 year');

        $stats = new StatsCheck();
        return $stats->byMonth($startDate, $endDate);
    }

    public function month_stats(Request $request)
    {
        $month = $request->query('month', null);
        if (is_null($month)) {
            abort(400, "month required");
        }

        $year = $request->query('year', null);
        if (is_null($year)) {
            abort(400, "year required");
        }

        $now = $this->timeNow();
        $startDate = $now->setDate($year, $month, 1)->modify('midnight');
        $endDate = $startDate->modify('+1 month');

        $stats = new StatsCheck();
        return $stats->byDaysOfMonth($startDate, $endDate);
    }

    private function timeNow()
    {
        $defaultTz = new \DateTimeZone('+03:00');
        return new \DateTimeImmutable('now', $defaultTz);
    }
}
