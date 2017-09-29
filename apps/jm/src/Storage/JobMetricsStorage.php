<?php
namespace App\Storage;

use Comrade\Shared\Model\JobMetrics;
use Makasim\Yadm\Storage;
use MongoDB\BSON\Javascript;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;

class JobMetricsStorage extends Storage
{
    public function countJobsPerPeriod(\DateTime $since, \DateTime $until, array $status = null, string $templateId = null)
    {
        $query = [
            'startTime.unix' => [
                '$gte' => (int) $since->format('U'),
                '$lte' => (int) $until->format('U'),
            ]
        ];

        if ($templateId) {
            $query['templateId'] = $templateId;
        }

        if ($status) {
            $query['status'] = [
                '$in' => $status,
            ];
        }

        return $this->count($query);
    }

    public function chart(\DateTime $since, \DateTime $until, $periodSec = null, array $status = null, string $templateId = null)
    {
        $sinceU = (int) $since->format('U');
        $untilU = (int) $until->format('U');

        // find actual since/until to calculate better time period
        $query = [
            'startTime.unix' => [
                '$gte' => $sinceU,
                '$lte' => $untilU,
            ]
        ];

        if ($templateId) {
            $query['templateId'] = $templateId;
        }

        if ($status) {
            $query['status'] = [
                '$in' => $status,
            ];
        }

        // calculate time period
        if (false == $periodSec) {
            $maxNumberPointsPerChart = 100;
            if (($untilU - $sinceU) > $maxNumberPointsPerChart) {
                $periodSec = (int) (($untilU - $sinceU) / $maxNumberPointsPerChart);
            } else {
                $periodSec = 1;
            }
        }

        $rangeSince = $sinceU - ((int)($periodSec / 2));
        $groupRanges = [];
        $ranges = [];
        $currentGroupRange = $rangeSince;
        $currentRange = $sinceU - $periodSec;
        do {
            $currentGroupRange += $periodSec;
            $groupRanges[] = $currentGroupRange;

            $currentRange += $periodSec;
            $ranges[] = $currentRange;
        } while ($currentGroupRange < $untilU);
        $rangeUntil = $currentGroupRange;

        $map = new Javascript('function () {
            var range = null;
            for (var i = 0; i < groupRanges.length; i++) {
                if (this.startTime.unix <= groupRanges[i]) {
                    range = ranges[i];
                    break;
                }
            }
            
            emit(range, {
              range: range,
              duration: this.duration,
              memory: this.memory,
              waitTime: this.waitTime,
              count: 1
            });
            
        }', ['groupRanges' => $groupRanges, 'ranges' => $ranges]);

        $reduce = new Javascript('function (key, values) {
            var result = {
              range: key,
              duration: 0,
              memory: 0,
              waitTime: 0,
              count: 0
            }
                        
            values.forEach(function (value) {
                result.duration += value.duration;
                result.memory += value.memory;
                result.waitTime += value.waitTime;
                result.count += value.count;
            });
            
            return result;
        }');

        $finalize = new Javascript('function (key, value) {
            var avrDuration = Math.round(value.duration / value.count);
            var avrWaitTime = Math.round(value.waitTime / value.count);

            return {
                range: key,
                avrDuration: avrDuration,
                avrMemory: Math.round(value.memory / value.count),
                avrWaitTime: avrWaitTime,
                throughput: Math.ceil(3600000 / (avrDuration + avrWaitTime)),
                jobsPerRange: value.count
            };
        }', ['period' => $periodSec]);

        $query = [
            'startTime.unix' => [
                '$gte' => $rangeSince,
                '$lte' => $rangeUntil,
            ]
        ];

        if ($templateId) {
            $query['templateId'] = $templateId;
        }

        if ($status) {
            $query['status'] = [
                '$in' => $status,
            ];
        }

        /** @var Cursor $cursor */
        $cursor = $this->getCollection()->getManager()->executeCommand($this->getCollection()->getDatabaseName(), new Command([
            'mapreduce' => $this->getCollection()->getCollectionName(),
            'map' => $map,
            'reduce' => $reduce,
            'finalize' => $finalize,
            'query' => $query,
            'sort' => ['startTime.unix' => 1],
            'out' => ['inline' => true],
        ]));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        $data = $cursor->toArray();

        $results = [];
        foreach ($data[0]['results'] as $result) {
            $results[] = $result['value'];
        }

        return $results;
    }

    public function accurateChart(int $since, int $until, array $status = null, string $templateId = null)
    {
        $query = [
            'startTime.unix' => [
                '$gte' => $since,
                '$lte' => $until,
            ]
        ];

        if ($templateId) {
            $query['templateId'] = $templateId;
        }

        if ($status) {
            $query['status'] = [
                '$in' => $status,
            ];
        }

        $result = [];
        foreach ($this->find($query) as $metric) {
            /** @var JobMetrics $metric */
            $result[] = [
                'range' => (int) $metric->getStartTime()->format('U'),
                'avrDuration' => $metric->getDuration(),
                'avrMemory' => $metric->getMemory(),
                'avrWaitTime' => $metric->getWaitTime(),
            ];
        }

        return $result;
    }
}
