<?php

namespace Bavix\XMLReader;

class Options
{

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * @var string
     */
    protected $rootName = 'bavix';

    /**
     * @var bool
     */
    protected $formatOutput = true;

    /**
     * @var string
     */
    protected $namespace = 'xmlns';

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getRootName(): string
    {
        return $this->rootName;
    }

    /**
     * @param string $rootName
     */
    public function setRootName(string $rootName): void
    {
        $this->rootName = $rootName;
    }

    /**
     * @return bool
     */
    public function isFormatOutput(): bool
    {
        return $this->formatOutput;
    }

    /**
     * @param bool $formatOutput
     */
    public function setFormatOutput(bool $formatOutput): void
    {
        $this->formatOutput = $formatOutput;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

}
