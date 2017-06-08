<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RunSubJobsPolicy implements Policy
{
    use CreateTrait;

    const SCHEMA = 'http://jm.forma-pro.com/schemas/RunSubJobsPolicy.json';

    private $values = [];

    /**
     * @param string $id
     */
    public function setSubProcessId(string $id):void
    {
        set_value($this, 'subProcessId', $id);
    }

    /**
     * @return string
     */
    public function getSubProcessId(): string
    {
        return get_value($this, 'subProcessId');
    }
}
