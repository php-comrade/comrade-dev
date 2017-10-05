<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

/**
 * @method RetryFailedPolicy getRetryFailedPolicy()
 */
class JobTemplate extends \Comrade\Shared\Model\JobTemplate
{
    /**
     * @return string
     */
    public function getProcessTemplateId(): string
    {
        return get_value($this,'processTemplateId');
    }

    /**
     * @param string $id
     */
    public function setProcessTemplateId(string $id): void
    {
        set_value($this, 'processTemplateId', $id);
    }
}