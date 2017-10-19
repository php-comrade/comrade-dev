<?php
namespace Comrade\Shared\Model;

use function Makasim\Values\get_value;
use function Makasim\Values\set_value;

class HttpRunner implements Runner
{
    const SCHEMA = 'http://comrade.forma-pro.com/schemas/runner/HttpRunner.json';

    use CreateTrait;

    /**
     * @var array
     */
    protected $values = [];

    public function setUrl(string $url):void
    {
        set_value($this, 'url', $url);
    }

    public function getUrl():string
    {
        return get_value($this, 'url');
    }

    public function isSync():bool
    {
        return get_value($this, 'sync', false);
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public static function createFor(string $url)
    {
        return static::create([
            'url' => $url,
        ]);
    }
}
