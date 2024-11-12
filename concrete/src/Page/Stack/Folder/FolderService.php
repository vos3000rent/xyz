<?php
namespace Concrete\Core\Page\Stack\Folder;

use Concrete\Core\Application\Application;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Page\Stack\StackList;
use Punic\Comparer;

class FolderService implements Container
{
    /**
     * @var \Concrete\Core\Database\Connection\Connection
     */
    protected $connection;

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $application;

    /**
     * @var \Concrete\Core\Page\Page|null
     */
    private $rootPage;

    /**
     * @var \Concrete\Core\Page\Type\Type|null
     */
    private $folderPageType;

    public function __construct(Application $application, Connection $connection)
    {
        $this->connection = $connection;
        $this->application = $application;
    }

    /**
     * @param string $path
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder|null
     */
    public function getByPath($path)
    {
        $c = Page::getByPath(STACKS_PAGE_PATH . '/' . trim($path, '/'));
        if ($c->getPageTypeHandle() == STACK_CATEGORY_PAGE_TYPE) {
            return $this->makeFolder($c);
        }
    }

    /**
     * @param int $cID
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder|null
     */
    public function getByID($cID)
    {
        $c = Page::getByID($cID);
        if ($c->getPageTypeHandle() == STACK_CATEGORY_PAGE_TYPE) {
            return $this->makeFolder($c);
        }
    }

    /**
     * @param string $name
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder
     */
    public function add($name, Folder $folder = null)
    {
        $parent = $folder ? $folder->getPage() : $this->getRootPage();
        $page = $parent->add($this->getFolderPageType(), [
            'name' => $name,
        ]);

        return $this->makeFolder($page);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getPage()
     */
    public function getPage()
    {
        return $this->getRootPage();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getParent()
     */
    public function getParent()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getFolders()
     */
    public function getFolders()
    {
        return $this->getChildFolders();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::createSubfolder()
     */
    public function createSubfolder($name)
    {
        return $this->add($name);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getGlobalAreas()
     */
    public function getGlobalAreas()
    {
        $stackList = new StackList();
        $stackList->filterByGlobalAreas();

        return $stackList->getResults();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\Stack\Folder\Container::getStacks()
     */
    public function getStacks()
    {
        return $this->getChildStacks(null);
    }

    /**
     * Get the folders contained in a folder.
     *
     * @param \Concrete\Core\Page\Stack\Folder\Folder|null $folder if NULL you'll have the root folders
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder[]
     */
    public function getChildFolders(Folder $parentFolder = null)
    {
        $parentPage = $parentFolder ? $parentFolder->getPage() : $this->getRootPage();
        $rs = $this->connection->executeQuery(
            'SELECT cID FROM Pages WHERE cParentID = :cParentID AND ptID = :ptID',
            [
                'cParentID' => $parentPage->getCollectionID(),
                'ptID' => $this->getFolderPageType()->getPageTypeID(),
            ]
        );
        $result = [];
        while (($cID = $rs->fetchOne()) !== false) {
            if (($folder = $this->getByID($cID)) !== null) {
                $result[] = $folder;
            }
        }
        $comparer = new Comparer();
        usort(
            $result,
            static function (Folder $a, Folder $b) use ($comparer) {
                $pageA = $a->getPage();
                $pageB = $b->getPage();

                return $comparer->compare($pageA->getCollectionName(), $pageB->getCollectionName()) ?: ($pageA->getCollectionID() - $pageA->getCollectionID);
            }
        );

        return $result;
    }

    /**
     * Get the stacks contained in a folder.
     *
     * @param \Concrete\Core\Page\Stack\Folder\Folder|null $folder if NULL you'll have the stacks in the folder
     *
     * @return \Concrete\Core\Page\Stack\Stack[]
     */
    public function getChildStacks(Folder $parentFolder = null)
    {
        $stackList = new StackList();
        $stackList->excludeGlobalAreas();
        $stackList->getQueryObject()->andWhere('p.ptID <> :folderPageType')->setParameter('folderPageType', $this->getFolderPageType()->getPageTypeID());
        if ($parentFolder === null) {
            $stackList->filterByParentID($this->getRootPage()->getCollectionID());
        } else {
            $stackList->filterByFolder($parentFolder);
        }

        return $stackList->getResults();
    }

    /**
     * @return \Concrete\Core\Page\Page|null returns NULL if it doesn't exist (shouldn't occur)
     */
    private function getRootPage()
    {
        if ($this->rootPage === null) {
            $rootPage = Page::getByPath(STACKS_PAGE_PATH);
            if ($rootPage && !$rootPage->isError()) {
                $this->rootPage = $rootPage;
            }
        }

        return $this->rootPage;
    }

    /**
     * @return \Concrete\Core\Page\Type\Type
     */
    private function getFolderPageType()
    {
        if ($this->folderPageType === null) {
            $this->folderPageType = Type::getByHandle(STACK_CATEGORY_PAGE_TYPE);
        }

        return $this->folderPageType;
    }

    /**
     * @return \Concrete\Core\Page\Stack\Folder\Folder
     */
    private function makeFolder(Page $page)
    {
        return $this->application->make(Folder::class, ['page' => $page, 'folderService' => $this]);
    }
}
