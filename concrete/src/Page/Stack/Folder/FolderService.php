<?php
namespace Concrete\Core\Page\Stack\Folder;

use Concrete\Core\Application\Application;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type;

class FolderService
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
            return $this->application->make('Concrete\Core\Page\Stack\Folder\Folder', array('page' => $c));
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
            return $this->application->make('Concrete\Core\Page\Stack\Folder\Folder', array('page' => $c));
        }
    }

    /**
     * @param string $name
     *
     * @return \Concrete\Core\Page\Stack\Folder\Folder
     */
    public function add($name, Folder $folder = null)
    {
        $type = Type::getByHandle(STACK_CATEGORY_PAGE_TYPE);
        $parent = $folder ? $folder->getPage() : $this->getRootPage();
        $data = array();
        $data['name'] = $name;
        $page = $parent->add($type, $data);

        return $this->application->make('Concrete\Core\Page\Stack\Folder\Folder', array('page' => $page));
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
}
