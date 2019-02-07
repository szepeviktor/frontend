<?php

namespace Studio24\Frontend\Content\Field;

/**
 * Represents one component
 *
 * @package Studio24\Frontend\Content\Field
 */
class Component
{
    protected $name;

    /**
     * Content field collection
     *
     * @var ContentFieldCollection
     */
    protected $content;

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
        $this->content = new ContentFieldCollection();
    }

    /**
     * Add new content field
     *
     * @param ContentFieldInterface $content
     * @return Component
     */
    public function addContent(ContentFieldInterface $content): Component
    {
        $this->content->addItem($content);
        return $this;
    }

    /**
     * Return collection of content fields
     *
     * @return ContentFieldCollection
     */
    public function getContent(): ContentFieldCollection
    {
        return $this->content;
    }

    /**
     * Get component name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set component name
     *
     * @param string $name
     * @return Component Fluent interface
     */
    public function setName(string $name): Component
    {
        $this->name = $name;
        return $this;
    }



}