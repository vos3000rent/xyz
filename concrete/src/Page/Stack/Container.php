<?php

namespace Concrete\Core\Page\Stack;

/**
 * An interface implemented by classes that provides folders and stacks
 */
interface Container
{
    /**
     * @return \Concrete\Core\Page\Page
     */
    public function getPage();

    /**
     * @return \Concrete\Core\Page\Stack\Container|null
     */
    public function getParent();

    /**
     * Get the child folders.
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder[]
     */
    public function getFolders();

    /**
     * Create a new folder.
     *
     * @param string $name
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder
     */
    public function createSubfolder($name);

    /**
     * @return \Concrete\Core\Page\Stack\Stack[]
     */
    public function getGlobalAreas();

    /**
     * @return \Concrete\Core\Page\Stack\Stack[]
     */
    public function getStacks();
}
