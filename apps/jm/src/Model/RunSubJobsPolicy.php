<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class RunSubJobsPolicy extends \Comrade\Shared\Model\RunSubJobsPolicy
{
    public function getCreatedSubJobsCount(): int
    {
        return get_value($this, 'createdSubJobsCount', 0);
    }

    public function setCreatedSubJobsCount(int $count): void
    {
        set_value($this, 'createdSubJobsCount', $count);
    }

    public function getFinishedSubJobsCount(): int
    {
        return get_value($this, 'finishedSubJobsCount', 0);
    }

    public function setFinishedSubJobsCount(int $count): void
    {
        set_value($this, 'finishedSubJobsCount', $count);
    }

    public function setFinished(bool $count): void
    {
        set_value($this, 'finished', $count);
    }

    public function isFinished(): bool
    {
        return get_value($this, 'finished', false);
    }
}
