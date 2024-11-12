<?php
namespace Concrete\Core\Page\Stack\Folder;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Export\ExportableInterface;
use Concrete\Core\Export\Item\StackFolder;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\AssignableObjectInterface;
use Concrete\Core\Permission\Key\Key;

class Folder implements Container, ExportableInterface, AssignableObjectInterface
{
    /**
     * @var \Concrete\Core\Page\Page
     */
    protected $page;

    /**
     * @var \Concrete\Core\Database\Connection\Connection
     */
    protected $connection;

    /**
     * @var \Concrete\Core\Page\Stack\Folder\FolderService
     */
    protected $folderService;

    public function __construct(Page $page, Connection $connection, FolderService $folderService)
    {
        $this->connection = $connection;
        $this->page = $page;
        $this->folderService = $folderService;
    }

    public function setChildPermissionsToOverride()
    {
        $this->page->setChildPermissionsToOverride();
    }

    public function setPermissionsToOverride()
    {
        $this->page->setPermissionsToOverride();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Concrete\Core\Permission\AssignableObjectInterface::assignPermissions()
     */
    public function assignPermissions(
        $userOrGroup,
        $permissions,
        $accessType = Key::ACCESS_TYPE_INCLUDE,
        $cascadeToChildren = true
    ) {
        $this->page->assignPermissions($userOrGroup, $permissions,$accessType, $cascadeToChildren);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Export\ExportableInterface::getExporter()
     */
    public function getExporter()
    {
        return new StackFolder();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getPage()
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getParent()
     */
    public function getParent()
    {
        return $this->folderService->getByID($this->getPage()->getCollectionParentID()) ?: $this->folderService;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getFolders()
     */
    public function getFolders()
    {
        return $this->folderService->getChildFolders($this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::createSubfolder()
     */
    public function createSubfolder($name)
    {
        return $this->folderService->add($name, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getGlobalAreas()
     */
    public function getGlobalAreas()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getGlobalAreas()
     */
    public function getStacks()
    {
        return $this->folderService->getChildStacks($this);
    }
}
