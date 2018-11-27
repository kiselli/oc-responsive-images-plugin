<?php

namespace OFFLINE\ResponsiveImages\Classes;

use Config;
use OFFLINE\ResponsiveImages\Models\Settings;
use PHPHtmlParser\Dom;


/**
 * Manipulates images in a DOMDocument.
 *
 * @package OFFLINE\ResponsiveImages\Classes
 */
class DomManipulator
{
    /**
     * @var \DOMNodeList
     */
    public $imgNodes;

    /**
     * Loads the html.
     *
     * @param                   $html
     * @param \DOMDocument|null $dom
     */
    public function __construct($html, Dom $dom = null)
    {

        if ($dom === null) {
            $this->dom = new Dom;
        }

        $dom->setOptions([
            'cleanupInput' => false,
            'removeScripts' => false,
            'removeStyles' => false,
            'preserveLineBreaks' => true
        ]);

        $this->dom->load(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $this->imgNodes = $this->dom->find("img");
    }

    /**
     * Returns an array of all img src attributes.
     *
     * @return array
     */
    public function getImageSources()
    {
        $images = [];

        foreach ($this->imgNodes as $node) {
            $images[] = $this->getSrcAttribute($node);
        }

        return $images;
    }

    /**
     * Adds srcset and sizes attributes to all local images
     * in the DOMDocument.
     *
     * @param $srcSets
     *
     * @return string
     */
    public function addSrcSetAttributes(array $srcSets)
    {
        foreach ($this->imgNodes as $node) {

            $src = $this->getSrcAttribute($node);

            if ( ! array_key_exists($src, $srcSets)) {
                // There are no alternative sizes available for this image
                continue;
            }

            $this->setSrcSetAttribute($node, $srcSets[$src]);
            $this->setSizesAttribute($node, $srcSets[$src]);
            $this->setClassAttribute($node);
        }

        return $this->dom->outerHtml;
    }

    /**
     * Set the sizes attribute based on the image's width attribute.
     *
     * @param           $node
     * @param SourceSet $sourceSet
     */
    protected function setSizesAttribute($node, SourceSet $sourceSet)
    {
        // Don't overwrite existing attributes
        if ($node->getAttribute('sizes') !== '') {
            return;
        }

        $node->setAttribute('sizes', $sourceSet->getSizesAttribute($node->getAttribute('width')));
    }

    /**
     * Set the srcset attribute.
     *
     * @param $node
     * @param $sourceSet
     */
    protected function setSrcSetAttribute($node, SourceSet $sourceSet)
    {
        $targetAttribute = Settings::get('alternative_src_set', 'srcset');
        if ( ! $targetAttribute) {
            $targetAttribute = 'srcset';
        }

        // Don't overwrite existing attributes
        if ($node->getAttribute($targetAttribute) !== '') {
            return;
        }

        $node->setAttribute($targetAttribute, $sourceSet->getSrcSetAttribute());
    }

    /**
     * Set the class attribute.
     *
     * @param $node
     */
    protected function setClassAttribute($node)
    {
        if ( ! $class = Settings::get('add_class')) {
            return;
        }

        $classes = $node->getAttribute('class');

        $node->setAttribute('class', "$classes $class");
    }

    /**
     * Normalize the image's src attribute and return it.
     *
     * @param $node
     *
     * @return mixed
     */
    protected function getSrcAttribute($node)
    {
        $src = $node->getAttribute('src');

        $altSrc = Settings::get('alternative_src', false);

        if ($altSrc && $node->getAttribute($altSrc) !== '') {
            $src = $node->getAttribute($altSrc);
        }

        return trim($src, '/');
    }

}
