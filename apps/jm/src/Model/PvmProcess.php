<?php
namespace App\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

/**
 * @method PvmToken getToken($id)
 */
class PvmProcess extends \Formapro\Pvm\Process
{
    public function setJobTemplateId(string $templateId): void
    {
        set_value($this, 'jobTemplateId', $templateId);
    }

    public function getJobTemplateId(): string
    {
        return get_value($this, 'jobTemplateId');
    }

    public function setJobId(string $templateId): void
    {
        set_value($this, 'jobId', $templateId);
    }

    public function getJobId(): string
    {
        return get_value($this, 'jobId');
    }
}
