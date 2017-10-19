<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use Makasim\Values\CastTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class GetJobChart implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/GetJobChart.json';

    use CreateTrait;
    use CastTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function setTemplateId(string $id) :void
    {
        set_value($this, 'templateId', $id);
    }

    public function getTemplateId() :string
    {
        return get_value($this,'templateId');
    }

    public function setStatuses(array $statuses)
    {
        set_value($this, 'statuses', $statuses);
    }

    public function getStatuses() :array
    {
        return get_value($this, 'status', []);
    }

    public function setSince(\DateTime $since) :void
    {
        set_value($this, 'since', $since);
    }

    public function getSince() :\DateTime
    {
        return get_value($this, 'since', null, \DateTime::class);
    }

    public function setUntil(\DateTime $until) :void
    {
        set_value($this, 'until', $until);
    }

    public function getUntil() :\DateTime
    {
        return get_value($this, 'until', null, \DateTime::class);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
