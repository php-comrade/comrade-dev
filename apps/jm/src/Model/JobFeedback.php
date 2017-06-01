<?php
namespace App\Model;

use App\Infra\Yadm\CreateTrait;
use Makasim\Values\ValuesTrait;

class JobFeedback
{
    const SCHEMA = 'http://jm.forma-pro.com/schemas/job-feedback.json';

    use CreateTrait;
    use ValuesTrait {
        setValue as public;
        getValue as public;
    }
}
