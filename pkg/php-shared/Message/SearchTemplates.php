<?php
namespace Comrade\Shared\Message;

use Comrade\Shared\Model\CreateTrait;
use function Makasim\Values\get_value;
use function Makasim\Values\get_values;
use function Makasim\Values\set_value;

class SearchTemplates implements \JsonSerializable
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/message/SearchTemplates.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function getTerm(): string
    {
        return get_value($this,'term', '');
    }

    public function setTerm(string $term): void
    {
        set_value($this,'term', $term);
    }

    public function getLimit(): int
    {
        return get_value($this,'limit', 10);
    }

    public function setLimit(int $limit): void
    {
        set_value($this,'term', $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_values($this);
    }
}
