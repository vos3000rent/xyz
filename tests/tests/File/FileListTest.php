<?php

namespace Concrete\Tests\File;

use Concrete\Core\Attribute\Key\Category;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\File\Filesystem;
use Concrete\Core\File\Import\FileImporter;
use Concrete\Core\File\Importer;
use Concrete\Core\File\Search\ColumnSet\Column\FileVersionFilenameColumn;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Concrete\TestHelpers\File\FileStorageTestCase;
use Core;

class FileListTest extends FileStorageTestCase
{
    /** @var \Concrete\Core\File\FileList */
    protected $list;

    public function __construct($name = '', array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->tables = array_merge($this->tables, [
            'PermissionAccessEntityTypes',
            'FileAttributeValues',
            'FileImageThumbnailTypes',
            'ConfigStore',
            'FileSets',
            'FileVersionLog',
            'FileSetFiles',
        ]);
        $this->metadatas = array_merge($this->metadatas, [
            'Concrete\Core\Entity\Attribute\Key\Settings\NumberSettings',
            'Concrete\Core\Entity\Attribute\Key\Settings\Settings',
            'Concrete\Core\Entity\Attribute\Key\Settings\EmptySettings',
            'Concrete\Core\Entity\Attribute\Key\FileKey',
            'Concrete\Core\Entity\Attribute\Value\FileValue',
            'Concrete\Core\Entity\User\User',
            'Concrete\Core\Entity\Attribute\Key\Key',
            'Concrete\Core\Entity\Attribute\Value\Value',
            'Concrete\Core\Entity\Attribute\Value\Value\NumberValue',
            'Concrete\Core\Entity\Attribute\Value\Value\Value',
            'Concrete\Core\Entity\Attribute\Type',
            'Concrete\Core\Entity\Attribute\Category',
            'Concrete\Core\Entity\File\Image\Thumbnail\Type\TypeFileSet',
        ]);
    }

    public static function setUpBeforeClass():void
    {
        parent::setUpBeforeClass();

        $files = \Core::make(Filesystem::class);
        $files->create();

        \Config::set('concrete.upload.extensions', '*.txt;*.jpg;*.jpeg;*.png');

        if (!Category::getByHandle('file')) {
            Category::add('file');
        }
        \Concrete\Core\Permission\Access\Entity\Type::add('file_uploader', 'File Uploader');
        if (!($number = AttributeType::getByHandle('number'))) {
            $number = AttributeType::add('number', 'Number');
        }
        FileKey::add($number, ['akHandle' => 'width', 'akName' => 'Width']);
        FileKey::add($number, ['akHandle' => 'height', 'akName' => 'Height']);

        $self = new static();
        if (!is_dir($self->getStorageDirectory())) {
            mkdir($self->getStorageDirectory());
        }
        $self->getStorageLocation();

        $sample = DIR_TESTS . '/assets/File/StorageLocation/sample.txt';
        $image = DIR_BASE . '/concrete/images/logo.png';
        $fi = Core::make(FileImporter::class);

        $files = [
            'sample1.txt' => $sample,
            'sample2.txt' => $sample,
            'sample4.txt' => $sample,
            'sample5.txt' => $sample,
            'awesome.txt' => $sample,
            'testing.txt' => $sample,
            'logo1.png' => $image,
            'logo2.png' => $image,
            'logo3.png' => $image,
            'foobley.png' => $image,
            'test.png' => $image,
        ];

        foreach ($files as $filename => $pointer) {
            $fi->importLocalFile($pointer, $filename);
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->list = new \Concrete\Core\File\FileList();
        $this->list->ignorePermissions();
    }

    public function testGetPaginationObject()
    {
        $pagination = $this->list->getPagination();
        $this->assertInstanceOf('\Concrete\Core\Search\Pagination\Pagination', $pagination);
    }

    public function testGetUnfilteredTotal()
    {
        $this->assertEquals(11, $this->list->getTotalResults());
    }

    public function testGetUnfilteredTotalFromPagination()
    {
        $pagination = $this->list->getPagination();
        $this->assertEquals(11, $pagination->getTotalResults());
    }

    public function testFilterByTypeValid1()
    {
        $this->list->filterByType(\Concrete\Core\File\Type\Type::T_IMAGE);
        $this->assertEquals(5, $this->list->getTotalResults());
        $pagination = $this->list->getPagination();
        $this->assertEquals(5, $pagination->getTotalResults());
        $pagination->setMaxPerPage(3)->setCurrentPage(1);
        $results = $pagination->getCurrentPageResults();
        $this->assertEquals(3, count($results));
        $this->assertInstanceOf('\Concrete\Core\Entity\File\File', $results[0]);
    }

    public function testFilterByExtensionAndType()
    {
        $this->list->filterByType(\Concrete\Core\File\Type\Type::T_TEXT);
        $this->list->filterByExtension('txt');
        $this->assertEquals(6, $this->list->getTotalResults());
    }

    public function testFilterByKeywords()
    {
        $this->list->filterByKeywords('le');
        $pagination = $this->list->getPagination();
        $this->assertEquals(5, $pagination->getTotalResults());
    }

    public function testFilterBySet()
    {
        $fs = \FileSet::add('test');
        $f = \File::getByID(1);
        $f2 = \File::getByID(4);
        $fs->addFileToSet($f);
        $fs->addFileToSet($f2);

        $fs2 = \FileSet::add('test2');
        $fs2->addFiletoSet($f);

        $this->list->filterBySet($fs);
        $pagination = $this->list->getPagination();
        $this->assertEquals(2, $pagination->getTotalResults());
        $results = $this->list->getResults();
        $this->assertEquals(2, count($results));
        $this->assertEquals(4, $results[1]->getFileID());

        $this->list->filterBySet($fs2);
        $results = $this->list->getResults();

        $this->assertEquals(1, count($results));
        $this->assertEquals(1, $results[0]->getFileID());

        $nl = new \Concrete\Core\File\FileList();
        $nl->ignorePermissions();
        $nl->filterByNoSet();
        $results = $nl->getResults();
        $this->assertEquals(9, count($results));
    }

    public function testSortByFilename()
    {
        $this->list->sortByFilenameAscending();
        $pagination = $this->list->getPagination();
        $pagination->setMaxPerPage(2);
        $results = $pagination->getCurrentPageResults();
        $this->assertEquals(2, count($results));
        $this->assertEquals(5, $results[0]->getFileID());
    }

    public function testAutoSort()
    {
        $req = \Request::getInstance();
        $req->query->set($this->list->getQuerySortColumnParameter(), 'fv.fvFilename');
        $req->query->set($this->list->getQuerySortDirectionParameter(), 'desc');
        $nl = new \Concrete\Core\File\FileList();
        $nl->ignorePermissions();
        $results = $nl->getResults();

        $this->assertEquals(6, $results[0]->getFileID());
        $this->assertEquals('testing.txt', $results[0]->getFilename());

        $req->query->set($this->list->getQuerySortColumnParameter(), null);
        $req->query->set($this->list->getQuerySortDirectionParameter(), null);
    }

    public function testPaginationPagesWithoutPermissions()
    {
        $pagination = $this->list->getPagination();
        $pagination->setMaxPerPage(2)->setCurrentPage(1);

        $this->assertEquals(6, $pagination->getTotalPages());

        $this->list->filterByType(\Concrete\Core\File\Type\Type::T_IMAGE);
        $pagination = $this->list->getPagination();
        $this->assertEquals(5, $pagination->getTotalResults());
        $pagination->setMaxPerPage(2)->setCurrentPage(2);

        $this->assertEquals(3, $pagination->getTotalPages());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertTrue($pagination->hasPreviousPage());

        $pagination->setCurrentPage(1);
        $this->assertTrue($pagination->hasNextPage());
        $this->assertFalse($pagination->hasPreviousPage());

        $pagination->setCurrentPage(3);
        $this->assertFalse($pagination->hasNextPage());
        $this->assertTrue($pagination->hasPreviousPage());

        $results = $pagination->getCurrentPageResults();
        $this->assertInstanceOf('\Concrete\Core\Entity\File\File', $results[0]);
        $this->assertCount(1, $results);
    }

    public function testPaginationWithPermissionsAndPager()
    {
        // first lets make some more files.
        $sample = DIR_TESTS . '/assets/File/StorageLocation/sample.txt';
        $image = DIR_BASE . '/concrete/images/logo.png';
        $fi = Core::make(FileImporter::class);

        $files = [
            'another.txt' => $sample,
            'funtime.txt' => $sample,
            'funtime2.txt' => $sample,
            'awesome-o.txt' => $sample,
            'image.png' => $image,
        ];

        foreach ($files as $filename => $pointer) {
            $fi->importLocalFile($pointer, $filename);
        }

        $nl = new \Concrete\Core\File\FileList();
        $nl->setPermissionsChecker(function ($file) {
            if ($file->getTypeObject()->getGenericType() == \Concrete\Core\File\Type\Type::T_IMAGE) {
                return true;
            } else {
                return false;
            }
        });
        $nl->sortBySearchColumn(new FileVersionFilenameColumn());
        $results = $nl->getResults();
        $factory = new PaginationFactory(\Request::createFromGlobals());
        $pagination = $factory->createPaginationObject($nl, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->assertEquals(-1, $nl->getTotalResults());
        $this->assertEquals(-1, $pagination->getTotalResults());
        $this->assertEquals(6, count($results));

        // so there are six "real" results, and 15 total results without filtering.
        $pagination->setMaxPerPage(4)->setCurrentPage(1);

        $this->assertEquals(-1, $pagination->getTotalPages());

        $this->assertTrue($pagination->hasNextPage());
        $this->assertFalse($pagination->hasPreviousPage());

        // Ok, so the results ought to be the following files, broken up into pages of four, in this order:
        // foobley.png
        // image.png
        // logo1.png
        // logo2.png
        // -- page break --
        // logo3.png
        // test.png

        $results = $pagination->getCurrentPageResults();

        $this->assertInstanceOf('\Concrete\Core\Search\Pagination\PagerPagination', $pagination);

        $this->assertEquals(4, count($results));

        $this->assertEquals('foobley.png', $results[0]->getFilename());
        $this->assertEquals('image.png', $results[1]->getFilename());
        $this->assertEquals('logo1.png', $results[2]->getFilename());
        $this->assertEquals('logo2.png', $results[3]->getFilename());

        $pagination->advanceToNextPage();

        $results = $pagination->getCurrentPageResults();

        $this->assertEquals('logo3.png', $results[0]->getFilename());
        $this->assertEquals('test.png', $results[1]->getFilename());
        $this->assertEquals(2, count($results));

        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertFalse($pagination->hasNextPage());
    }

    public function testPaginationThatStopsOnTheFirstPage()
    {
        // first lets make some more files.
        $sample = DIR_TESTS . '/assets/File/StorageLocation/sample.txt';
        $image = DIR_BASE . '/concrete/images/logo.png';
        $fi = new Importer();

        $files = [
            'another.txt' => $sample,
            'funtime.txt' => $sample,
            'funtime2.txt' => $sample,
            'awesome-o.txt' => $sample,
            'image.png' => $image,
        ];

        foreach ($files as $filename => $pointer) {
            $fi->import($pointer, $filename);
        }

        $nl = new \Concrete\Core\File\FileList();
        $nl->setPermissionsChecker(function ($file) {
            if ($file->getFileID() < 3) {
                return true;
            }

            return false;
        });
        $nl->sortBySearchColumn(new FileVersionFilenameColumn());
        $results = $nl->getResults();
        $factory = new PaginationFactory(\Request::createFromGlobals());
        $pagination = $factory->createPaginationObject($nl, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->assertEquals(-1, $nl->getTotalResults());
        $this->assertEquals(-1, $pagination->getTotalResults());
        $this->assertEquals(2, count($results));

        $results = $pagination->getCurrentPageResults();

        $this->assertEquals('sample1.txt', $results[0]->getFilename());
        $this->assertEquals('sample2.txt', $results[1]->getFilename());
        $this->assertFalse($pagination->hasNextPage());
        $this->assertEquals(2, count($results));
        $this->assertTrue(!isset($results[2]));
    }

    protected function cleanup()
    {
        parent::cleanup();
        if (file_exists(__DIR__ . '/test.txt')) {
            unlink(__DIR__ . '/test.txt');
        }
        if (file_exists(__DIR__ . '/test.invalid')) {
            unlink(__DIR__ . '/test.invalid');
        }
    }
}
