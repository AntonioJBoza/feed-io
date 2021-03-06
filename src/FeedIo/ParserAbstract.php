<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo;

use FeedIo\Parser\UnsupportedFormatException;
use FeedIo\Reader\Document;
use FeedIo\Feed\ItemInterface;
use FeedIo\Feed\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Parses a document if its format matches the parser's standard
 *
 * Depends on :
 *  - FeedIo\StandardAbstract
 *  - Psr\Log\LoggerInterface
 *
 */
abstract class ParserAbstract
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array[FilterInterface]
     */
    protected $filters = array();

    /**
     * @var StandardAbstract
     */
    protected $standard;

    /**
     * @param StandardAbstract $standard
     * @param LoggerInterface  $logger
     */
    public function __construct(StandardAbstract $standard, LoggerInterface $logger)
    {
        $this->standard = $standard;
        $this->logger = $logger;
    }

    /**
     * Tries to parse the document
     *
     * @param Document $document
     * @param FeedInterface $feed
     * @return \FeedIo\FeedInterface
     * @throws \FeedIo\Parser\UnsupportedFormatException
     */
    public function parse(Document $document, FeedInterface $feed)
    {
        if (!$this->standard->canHandle($document)) {
            throw new UnsupportedFormatException('this is not a supported format');
        }

        $this->checkBodyStructure($document, $this->standard->getMandatoryFields());
        $this->parseContent($document, $feed);

        return $feed;
    }

    /**
     * This method is called by parse() if and only if the checkBodyStructure was successful
     *
     * @param Document $document
     * @param FeedInterface $feed
     * @return \FeedIo\FeedInterface
     */
    abstract public function parseContent(Document $document, FeedInterface $feed);

    /**
     * @param  Document            $document
     * @param  array               $mandatoryFields
     * @return $this
     * @throws MissingFieldsException
     */
    abstract public function checkBodyStructure(Document $document, array $mandatoryFields);

    /**
     * @return StandardAbstract
     */
    public function getStandard()
    {
        return $this->standard;
    }

    /**
     * @param  FeedInterface $feed
     * @param  NodeInterface $item
     * @return $this
     */
    public function addValidItem(FeedInterface $feed, NodeInterface $item)
    {
        if ($item instanceof ItemInterface && $this->isValid($item)) {
            $feed->add($item);
        }

        return $this;
    }

    /**
     * @param  ItemInterface $item
     * @return bool
     */
    public function isValid(ItemInterface $item)
    {
        foreach ($this->filters as $filter) {
            if (!$filter->isValid($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  FilterInterface $filter
     * @return $this
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Reset filters
     * @return $this
     */
    public function resetFilters()
    {
        $this->filters = [];

        return $this;
    }
}
