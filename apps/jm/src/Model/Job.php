<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class Job extends JobPattern
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/job.json';

    /**
     * @var array
     */
    private $values = [];

    public static function createFromPattern(JobPattern $jobPattern) : Job
    {
        $values = get_values($jobPattern);
        unset($values['schema']);

        return static::create($values);
    }
}
