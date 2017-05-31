<?php
namespace App\Infra\JsonSchema;

use JsonSchema\Uri\Retrievers\FileGetContents;
use JsonSchema\Uri\Retrievers\UriRetrieverInterface;

class LocalUriRetriver implements UriRetrieverInterface
{
    /**
     * @var
     */
    private $baseDir;

    /**
     * @var
     */
    private $baseUri;

    /**
     * @var UriRetrieverInterface|null
     */
    private $fallbackRetriever;

    /**
     * @var FileGetContents
     */
    private $filesystemRetriever;

    /**
     * @var UriRetrieverInterface
     */
    private $lastUsedRetriever;

    /**
     * @param string $baseDir
     * @param string $baseUri
     * @param UriRetrieverInterface|null $fallbackRetriever
     */
    public function __construct($baseDir, $baseUri, UriRetrieverInterface $fallbackRetriever = null)
    {
        $this->baseDir = realpath($baseDir);
        $this->baseUri = $baseUri;

        $this->filesystemRetriever = new FileGetContents();
        $this->fallbackRetriever = $fallbackRetriever ?: $this->filesystemRetriever;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uri)
    {
        $this->lastUsedRetriever = null;

        if (0 !== strpos($uri, $this->baseUri)) {
            $this->lastUsedRetriever = $this->fallbackRetriever;

            return $this->fallbackRetriever->retrieve($uri);
        }

        $localUri = 'file://'.str_replace($this->baseUri, $this->baseDir, $uri);

        return $this->filesystemRetriever->retrieve($localUri);
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->lastUsedRetriever ? $this->lastUsedRetriever->getContentType() : 'application/schema+json';
    }
}
