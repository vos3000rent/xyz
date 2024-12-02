<?php
namespace Concrete\Block\PageList;

use BlockType;
use CollectionAttributeKey;
use Concrete\Core\Attribute\Category\PageCategory;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Block\View\BlockView;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Entity\Site\Site;
use Concrete\Core\Feature\Features;
use Concrete\Core\Feature\UsesFeatureInterface;
use Concrete\Core\Html\Service\Seo;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Package\Offline\Exception;
use Concrete\Core\Page\Feed;
use Concrete\Core\Tree\Node\Node;
use Concrete\Core\Tree\Node\Type\Topic;
use Core;
use Concrete\Core\Url\SeoCanonical;
use Database;
use Page;
use PageList;

class Controller extends BlockController implements UsesFeatureInterface
{
    /**
     * @var int|string|null
     */
    public $num;

    /**
     * @var string|null
     */
    public $orderBy;

    /**
     * @var int|string|null
     */
    public $cParentID;

    /**
     * @var bool|int|string|null
     */
    public $cThis;

    /**
     * @var bool|int|string|null
     */
    public $cThisParent;

    /**
     * @var bool|int|string|null
     */
    public $useButtonForLink;

    /**
     * @var string|null
     */
    public $buttonLinkText;

    /**
     * @var string|null
     */
    public $pageListTitle;

    /**
     * @var bool|int|string|null
     */
    public $filterByRelated;

    /**
     * @var bool|int|string|null
     */
    public $filterByCustomTopic;

    /**
     * @var string|null
     */
    public $filterDateOption;

    /**
     * @var int|string|null
     */
    public $filterDateDays;

    /**
     * @var string|null
     */
    public $filterDateStart;

    /**
     * @var string|null
     */
    public $filterDateEnd;

    /**
     * @var string|null
     */
    public $relatedTopicAttributeKeyHandle;

    /**
     * @var string|null
     */
    public $customTopicAttributeKeyHandle;

    /**
     * @var int|string|null
     */
    public $customTopicTreeNodeID;

    /**
     * @var bool|int|string|null
     */
    public $includeName;

    /**
     * @var bool|int|string|null
     */
    public $includeDescription;

    /**
     * @var bool|int|string|null
     */
    public $includeDate;

    /**
     * @var bool|int|string|null
     */
    public $includeAllDescendents;

    /**
     * @var bool|int|string|null
     */
    public $paginate;

    /**
     * @var bool|int|string|null
     */
    public $excludeCanonicalPaging;

    /**
     * @var bool|int|string|null
     */
    public $displayAliases;

    /**
     * @var bool|int|string|null
     */
    public $displaySystemPages;

    /**
     * @var bool|int|string|null
     */
    public $ignorePermissions;

    /**
     * @var bool|int|string|null
     */
    public $enableExternalFiltering;

    /**
     * @var bool|int|string|null
     */
    public $excludeCurrentPage;

    /**
     * @var int|string|null
     */
    public $ptID;

    /**
     * @var int|string|null
     */
    public $pfID;

    /**
     * @var int|string|null
     */
    public $truncateSummaries;

    /**
     * @var bool|int|string|null
     */
    public $displayFeaturedOnly;

    /**
     * @var string|null
     */
    public $noResultsMessage;

    /**
     * @var bool|int|string|null
     */
    public $displayThumbnail;

    /**
     * @var int|string|null
     */
    public $truncateChars;

    /**
     * @var string|null
     */
    public $titleFormat;

    protected $btTable = 'btPageList';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 525;
    protected $btExportPageColumns = ['cParentID'];
    protected $btExportPageTypeColumns = ['ptID'];
    protected $btExportPageFeedColumns = ['pfID'];
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = null;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputLifetime = 300;
    protected $list;

    /**
     * @deprecated What's deprecated is the "public" part.
     *
     * @var int|null
     */
    public $cID;

    /**
     * @deprecated What's deprecated is the "public" part.
     *
     * @var int|null
     */
    public $cPID;

    public function getRequiredFeatures(): array
    {
        return [
            Features::NAVIGATION,
        ];
    }

    /**
     * Used for localization. If we want to localize the name/description we have to include this.
     */
    public function getBlockTypeDescription()
    {
        return t('List pages based on type, area.');
    }

    public function getBlockTypeName()
    {
        return t('Page List');
    }

    public function action_preview_pane()
    {
        $bt = BlockType::getByHandle('page_list');
        $controller = $bt->getController();

        // @TODO - clean up this old code.

        $_REQUEST['num'] = ($_REQUEST['num'] > 0) ? $_REQUEST['num'] : 0;
        $_REQUEST['cThis'] = ($_REQUEST['cParentID'] == $_REQUEST['current_page']) ? '1' : '0';
        $_REQUEST['cParentID'] = ($_REQUEST['cParentID'] == 'OTHER') ? $_REQUEST['cParentIDValue'] : $_REQUEST['cParentID'];

        if ($_REQUEST['filterDateOption'] != 'between') {
            $_REQUEST['filterDateStart'] = null;
            $_REQUEST['filterDateEnd'] = null;
        }

        if ($_REQUEST['filterDateOption'] == 'past') {
            $_REQUEST['filterDateDays'] = $_REQUEST['filterDatePast'];
        } elseif ($_REQUEST['filterDateOption'] == 'future') {
            $_REQUEST['filterDateDays'] = $_REQUEST['filterDateFuture'];
        } else {
            $_REQUEST['filterDateDays'] = null;
        }

        $controller->num = $_REQUEST['num'];
        $controller->cParentID = $_REQUEST['cParentID'];
        $controller->cThis = $_REQUEST['cThis'];
        $controller->orderBy = $_REQUEST['orderBy'];
        $controller->ptID = $_REQUEST['ptID'];
        $controller->rss = $_REQUEST['rss'];
        $controller->displayFeaturedOnly = $_REQUEST['displayFeaturedOnly'] ?? false;
        $controller->displayAliases = $_REQUEST['displayAliases'] ?? false;
        $controller->paginate = $_REQUEST['paginate'] ?? false;
        $controller->excludeCanonicalPaging = $_REQUEST['excludeCanonicalPaging'] ?? false;
        $controller->enableExternalFiltering = $_REQUEST['enableExternalFiltering'] ?? false;
        $controller->excludeCurrentPage = $_REQUEST['excludeCurrentPage'] ?? false;
        $controller->filterByRelated = $_REQUEST['filterByRelated'] ?? false;
        $controller->relatedTopicAttributeKeyHandle = $_REQUEST['relatedTopicAttributeKeyHandle'];
        $controller->filterByCustomTopic = ($_REQUEST['topicFilter'] == 'custom') ? '1' : '0';
        $controller->customTopicAttributeKeyHandle = $_REQUEST['customTopicAttributeKeyHandle'];
        $controller->customTopicTreeNodeID = $_REQUEST['customTopicTreeNodeID'];
        $controller->includeAllDescendents = $_REQUEST['includeAllDescendents'] ?? false;
        $controller->includeDate = $_REQUEST['includeDate'] ?? false;
        $controller->displayThumbnail = $_REQUEST['displayThumbnail'] ?? false;
        $controller->includeDescription = $_REQUEST['includeDescription'] ?? false;
        $controller->useButtonForLink = $_REQUEST['useButtonForLink'] ?? false;
        $controller->filterDateOption = $_REQUEST['filterDateOption'];
        $controller->filterDateStart = $_REQUEST['filterDateStart'];
        $controller->filterDateEnd = $_REQUEST['filterDateEnd'];
        $controller->filterDateDays = $_REQUEST['filterDateDays'];
        $controller->noResultsMessage = $_REQUEST['noResultsMessage'];
        $controller->set('includeEntryText', true);
        $controller->set('includeName', true);
        $controller->set('displayThumbnail', $controller->displayThumbnail);
        $controller->set('noResultsMessage', $controller->noResultsMessage);
        $bv = new BlockView($bt);
        ob_start();
        $bv->render('view');
        $content = ob_get_contents();
        ob_end_clean();

        return $this->app->make(ResponseFactoryInterface::class)->create($content);
    }


    public function on_start()
    {
        $this->list = new PageList();
        $this->list->disableAutomaticSorting();
        $this->list->setNameSpace('b' . $this->bID);
        $expr = $this->list->getQueryObject()->expr(); // Get Query Expression Object

        $cArray = [];

        switch ($this->orderBy) {
            case 'display_asc':
                $this->list->sortByDisplayOrder();
                break;
            case 'display_desc':
                $this->list->sortByDisplayOrderDescending();
                break;
            case 'chrono_asc':
                $this->list->sortByPublicDate();
                break;
            case 'modified_desc':
                $this->list->sortByDateModifiedDescending();
                break;
            case 'random':
                $this->list->sortBy('RAND()');
                break;
            case 'alpha_asc':
                $this->list->sortByName();
                break;
            case 'alpha_desc':
                $this->list->sortByNameDescending();
                break;
            default:
                $this->list->sortByPublicDateDescending();
                break;
        }

        $now = Core::make('helper/date')->toDB();
        $end = $start = null;

        switch ($this->filterDateOption) {
            case 'now':
                $start = date('Y-m-d') . ' 00:00:00';
                $end = $now;
                break;

            case 'past':
                $end = $now;

                if ($this->filterDateDays > 0) {
                    $past = date('Y-m-d', strtotime("-{$this->filterDateDays} days"));
                    $start = "$past 00:00:00";
                }
                break;

            case 'future':
                $start = $now;

                if ($this->filterDateDays > 0) {
                    $future = date('Y-m-d', strtotime("+{$this->filterDateDays} days"));
                    $end = "$future 23:59:59";
                }
                break;

            case 'between':
                $start = "{$this->filterDateStart} 00:00:00";
                $end = "{$this->filterDateEnd} 23:59:59";
                break;

            case 'all':
            default:
                break;
        }

        if ($start) {
            $this->list->filterByPublicDate($start, '>=');
        }
        if ($end) {
            $this->list->filterByPublicDate($end, '<=');
        }

        $c = Page::getCurrentPage();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        if ($this->displayFeaturedOnly == 1) {
            if ($this->checkSearchablePageAttributeKey('is_featured') === '') {
                $this->list->filterByIsFeatured(1);
            }
        }
        if ($this->displayAliases) {
            $this->list->includeAliases();
        }
        if ($this->displaySystemPages) {
            $this->list->includeSystemPages();
        }
        if (isset($this->ignorePermissions) && $this->ignorePermissions) {
            $this->list->ignorePermissions();
        }
        if ($this->excludeCurrentPage) {
            $ID = Page::getCurrentPage()->getCollectionID();
            $this->list->getQueryObject()->andWhere($expr->neq('p.cID', $ID));
        }

        $this->list->filter('cvName', '', '!=');

        if ($this->ptID) {
            $this->list->filterByPageTypeID($this->ptID);
        }

        if ($this->filterByRelated) {
            $ak = CollectionKey::getByHandle($this->relatedTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $topics = $c->getAttribute($ak->getAttributeKeyHandle());
                if (is_array($topics) && count($topics) > 0) {
                    $topic = $topics[array_rand($topics)];
                    $this->list->filter('p.cID', $c->getCollectionID(), '<>');
                    $this->list->filterByTopic($topic);
                }
            }
        }

        if ($this->filterByCustomTopic) {
            $ak = CollectionKey::getByHandle($this->customTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $topic = Node::getByID($this->customTopicTreeNodeID);
                if ($topic) {
                    $ak->getController()->filterByAttribute($this->list, $this->customTopicTreeNodeID);
                }
            }
        }

        $this->list->filterByExcludePageList(false);

        if ((int) ($this->cParentID) != 0) {
            $cParentID = ($this->cThis) ? $this->cID : (($this->cThisParent) ? $this->cPID : $this->cParentID);
            if ($this->includeAllDescendents) {
                $this->list->filterByPath(Page::getByID($cParentID)->getCollectionPath());
            } else {
                $this->list->filterByParentID($cParentID);
            }
        }

        if ($this->paginate && !$this->excludeCanonicalPaging) {
            $paging = $this->request->request($this->list->getQueryPaginationPageParameter());
            if ($paging && $paging >= 2) { // Canonicalize page 2 and greater only
                /** @var SeoCanonical $seoCanonical */
                $seoCanonical = $this->app->make(SeoCanonical::class);
                $seoCanonical->addIncludedQuerystringParameter($this->list->getQueryPaginationPageParameter());
            }
        }

        return $this->list;
    }

    public function view()
    {
        $list = $this->list;
        $nh = Core::make('helper/navigation');
        $this->set('nh', $nh);

        if ($this->pfID) {
            $this->requireAsset('css', 'font-awesome');
            $feed = Feed::getByID($this->pfID);
            if (is_object($feed)) {
                $this->set('rssUrl', $feed->getFeedURL());
                $link = $feed->getHeadLinkElement();
                $this->addHeaderItem($link);
            }
        }

        //Pagination...
        $showPagination = false;
        if ($this->num > 0) {
            $list->setItemsPerPage($this->num);
            $pagination = $list->getPagination();
            $pages = $pagination->getCurrentPageResults();
            if ($pagination->haveToPaginate() && $this->paginate) {
                $showPagination = true;
                $pagination = $pagination->renderDefaultView();
                $this->set('pagination', $pagination);
            }
        } else {
            $pages = $list->getResults();
        }

        if ($showPagination) {
            $this->requireAsset('css', 'core/frontend/pagination');
        }
        $this->set('pages', $pages);
        $this->set('list', $list);
        $this->set('showPagination', $showPagination);
    }

    public function add()
    {
        $c = Page::getCurrentPage();
        $uh = Core::make('helper/concrete/urls');
        $this->set('c', $c);
        $this->set('uh', $uh);
        $this->set('includeDescription', true);
        $this->set('includeName', true);
        $this->set('bt', BlockType::getByHandle('page_list'));
        $this->set('featuredAttributeUnusableReason', $this->checkSearchablePageAttributeKey('is_featured'));
        $this->set('thumbnailAttribute', CollectionAttributeKey::getByHandle('thumbnail'));
        $this->set('titleFormat', 'h5');
        $this->set('topicFilter', '');
        $this->set('filterDateOption', 'all');
        $this->set('num', 10);
        $this->set('ptID', 0);
        $this->set('customTopicAttributeKeyHandle', null);
        $this->set('relatedTopicAttributeKeyHandle', null);
        $this->set('customTopicTreeNodeID', 0);
        $this->set('filterDateDays', 0);
        $this->set('filterDateStart', null);
        $this->set('filterDateEnd', null);
        $this->set('displayFeaturedOnly', false);
        $this->set('displayAliases', false);
        $this->set('displaySystemPages', false);
        $this->set('ignorePermissions', false);
        $this->set('enableExternalFiltering', false);
        $this->set('excludeCurrentPage', false);
        $this->set('paginate', false);
        $this->set('excludeCanonicalPaging', false);
        $this->set('cParentID', 0);
        $this->set('cThis', false);
        $this->set('cThisParent', false);
        $this->set('isOtherPage', false);
        $this->set('includeAllDescendents', false);
        $this->set('orderBy', $this->orderBy);
        $this->set('rssFeed', false);
        $this->set('truncateSummaries', false);
        $this->set('truncateChars', 0);
        $this->set('includeDate', false);
        $this->set('displayThumbnail', false);
        $this->set('useButtonForLink', false);
        $this->set('buttonLinkText', null);
        $this->set('pageListTitle', false);
        $this->set('noResultsMessage', false);
        $this->loadKeys();
    }

    public function edit()
    {
        $b = $this->getBlockObject();
        $bID = $b->getBlockID();
        $this->set('bID', $bID);
        if ((!$this->cThis) && (!$this->cThisParent) && ($this->cParentID != 0)) {
            $this->set('isOtherPage', true);
        }
        if ($this->pfID) {
            $feed = Feed::getByID($this->pfID);
            if (is_object($feed)) {
                $this->set('rssFeed', $feed);
            }
        }
        $uh = Core::make('helper/concrete/urls');
        $this->set('uh', $uh);
        $this->set('bt', BlockType::getByHandle('page_list'));
        $this->set('featuredAttributeUnusableReason', $this->checkSearchablePageAttributeKey('is_featured'));
        $this->set('thumbnailAttribute', CollectionAttributeKey::getByHandle('thumbnail'));
        $topicFilter = '';
        if ($this->filterByRelated) {
            $topicFilter = 'related';
        }elseif ($this->filterByCustomTopic) {
            $topicFilter = 'custom';
        }
        $this->set('topicFilter', $topicFilter);
        $this->loadKeys();
    }

    public function action_filter_by_topic($treeNodeID = false, $topic = false)
    {
        if ($treeNodeID) {
            $topicObj = Topic::getByID((int) $treeNodeID);
            if (is_object($topicObj) && $topicObj instanceof Topic) {
                $this->list->filterByTopic((int) $treeNodeID);

                /** @var Seo $seo */
                $seo = $this->app->make('helper/seo');
                $seo->addTitleSegment($topicObj->getTreeNodeDisplayName());

                /** @var SeoCanonical $canonical */
                $canonical = $this->app->make(SeoCanonical::class);
                $canonical->setPathArguments(['topic', $treeNodeID, $topic]);
            }
        }
        $this->view();
    }

    public function action_filter_by_tag($tag = false)
    {
        /** @var Seo $seo */
        $seo = $this->app->make('helper/seo');
        $seo->addTitleSegment($tag);

        /** @var SeoCanonical $canonical */
        $canonical = $this->app->make(SeoCanonical::class);
        $canonical->setPathArguments(['tag', $tag]);

        $this->list->filterByTags(h($tag));
        $this->view();
    }

    public function action_search_keywords($bID)
    {
        if ($bID == $this->bID) {
            $keywords = h($this->request->query->get('keywords'));
            $this->list->filterByKeywords($keywords);
            $this->view();
        }
    }

    public function action_filter_by_date($year = false, $month = false, $timezone = 'user')
    {
        if (is_numeric($year)) {
            $year = (($year < 0) ? '-' : '') . str_pad(abs($year), 4, '0', STR_PAD_LEFT);
            if ($month) {
                $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                $lastDayInMonth = date('t', strtotime("$year-$month-01"));
                $start = "$year-$month-01 00:00:00";
                $end = "$year-$month-$lastDayInMonth 23:59:59";
            } else {
                $start = "$year-01-01 00:00:00";
                $end = "$year-12-31 23:59:59";
            }
            $dh = Core::make('helper/date');
            /* @var $dh \Concrete\Core\Localization\Service\Date */
            if ($timezone !== 'system') {
                $start = $dh->toDB($start, $timezone);
                $end = $dh->toDB($end, $timezone);
            }
            $this->list->filterByPublicDate($start, '>=');
            $this->list->filterByPublicDate($end, '<=');

            /** @var Seo $seo */
            $seo = $this->app->make('helper/seo');
            $date = ucfirst(\Punic\Calendar::getMonthName($month, 'wide', '', true) . ' ' . $year);
            $seo->addTitleSegment($date);

            /** @var SeoCanonical $canonical */
            $canonical = $this->app->make(SeoCanonical::class);
            $canonical->setPathArguments([$year, $month]);
        }
        $this->view();
    }

    public function validate($args)
    {
        $args += [
            'rss' => null,
            'rssHandle' => '',
            'rssTitle' => '',
            'rssDescription' => ''
        ];

        $e = Core::make('helper/validation/error');
        $vs = Core::make('helper/validation/strings');
        $pf = false;
        if ($this->pfID) {
            $pf = Feed::getByID($this->pfID);
        }
        if ($args['rss'] && !is_object($pf)) {
            if (!$vs->alphanum($args['rssHandle'], false, true)) {
                $e->add(t('Your RSS feed must have a valid URL, containing only letters, numbers or hyphens'));
            }
            if (!$vs->notempty($args['rssTitle'])) {
                $e->add(t('Your RSS feed must have a valid title.'));
            }
            if (!$vs->notempty($args['rssDescription'])) {
                $e->add(t('Your RSS feed must have a valid description.'));
            }
        }

        return $e;
    }

    public function getPassThruActionAndParameters($parameters)
    {
        if ($parameters[0] == 'preview_pane') {
            return parent::getPassThruActionAndParameters($parameters);
        }

        if ($parameters[0] == 'topic') {
            $method = 'action_filter_by_topic';
            $parameters = array_slice($parameters, 1);
        } elseif ($parameters[0] == 'tag') {
            $method = 'action_filter_by_tag';
            $parameters = array_slice($parameters, 1);
        } elseif (Core::make('helper/validation/numbers')->integer($parameters[0])) {
            // then we're going to treat this as a year.
            $method = 'action_filter_by_date';
            $parameters[0] = (int)($parameters[0]);
            if (isset($parameters[1])) {
                $parameters[1] = (int)($parameters[1]);
            }
        } else if ($parameters[0] == 'search_keywords') {
            return parent::getPassThruActionAndParameters($parameters);
        } else {
            $parameters = $method = null;
        }

        return [$method, $parameters];
    }

    public function isValidControllerTask($method, $parameters = [])
    {
        if ($method == 'action_preview_pane') {
            return true;
        }

        if (!$this->enableExternalFiltering) {
            return false;
        }

        if ($method === 'action_filter_by_date') {
            // Parameter 0 must be set
            if (!isset($parameters[0]) || $parameters[0] < 0 || $parameters[0] > 9999) {
                return false;
            }
            // Parameter 1 can be null
            if (isset($parameters[1])) {
                if ($parameters[1] < 1 || $parameters[1] > 12) {
                    return false;
                }
            }
        }

        return parent::isValidControllerTask($method, $parameters);
    }

    public function save($args)
    {
        // If we've gotten to the process() function for this class, we assume that we're in
        // the clear, as far as permissions are concerned (since we check permissions at several
        // points within the dispatcher)
        $db = Database::connection();

        $bID = $this->bID;
        $c = $this->getCollectionObject();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        $args += [
            'enableExternalFiltering' => 0,
            'includeAllDescendents' => 0,
            'includeDate' => 0,
            'truncateSummaries' => 0,
            'displayFeaturedOnly' => 0,
            'topicFilter' => '',
            'displayThumbnail' => 0,
            'displayAliases' => 0,
            'displaySystemPages' => 0,
            'excludeCurrentPage' => 0,
            'truncateChars' => 0,
            'paginate' => 0,
            'excludeCanonicalPaging' => 0,
            'rss' => 0,
            'pfID' => 0,
            'ptID' => 0,
            'filterDateOption' => 'all',
            'cParentID' => null,
            'ignorePermissions' => 0,
        ];

        if (is_numeric($args['cParentID'])) {
            $args['cParentID'] = (int) ($args['cParentID']);
        }

        $args['num'] = ($args['num'] > 0) ? $args['num'] : 0;
        $args['cThis'] = ($args['cParentID'] === $this->cID) ? '1' : '0';
        $args['cThisParent'] = ($args['cParentID'] === $this->cPID) ? '1' : '0';
        $args['cParentID'] = ($args['cParentID'] === 'OTHER') ? (empty($args['cParentIDValue']) ? null : $args['cParentIDValue']) : $args['cParentID'];
        if (!$args['cParentID']) {
            $args['cParentID'] = 0;
        }
        $args['enableExternalFiltering'] = ($args['enableExternalFiltering']) ? '1' : '0';
        $args['includeAllDescendents'] = ($args['includeAllDescendents']) ? '1' : '0';
        $args['includeDate'] = ($args['includeDate']) ? '1' : '0';
        $args['truncateSummaries'] = ($args['truncateSummaries']) ? '1' : '0';
        $args['displayFeaturedOnly'] = ($args['displayFeaturedOnly']) ? '1' : '0';
        $args['filterByRelated'] = ($args['topicFilter'] == 'related') ? '1' : '0';
        $args['filterByCustomTopic'] = ($args['topicFilter'] == 'custom') ? '1' : '0';
        $args['displayThumbnail'] = ($args['displayThumbnail']) ? '1' : '0';
        $args['displayAliases'] = ($args['displayAliases']) ? '1' : '0';
        $args['displaySystemPages'] = ($args['displaySystemPages']) ? '1' : '0';
        $args['excludeCurrentPage'] = ($args['excludeCurrentPage']) ? '1' : '0';
        $args['truncateChars'] = (int) ($args['truncateChars']);
        $args['paginate'] = (int) ($args['paginate']);
        $args['excludeCanonicalPaging'] = (int) ($args['excludeCanonicalPaging']);
        $args['rss'] = (int) ($args['rss']);
        $args['ptID'] = (int) ($args['ptID']);

        if (!$args['filterByRelated']) {
            $args['relatedTopicAttributeKeyHandle'] = '';
        }

        if (!$args['filterByCustomTopic'] || !$this->app->make('helper/number')->isInteger($args['customTopicTreeNodeID'])) {
            $args['customTopicAttributeKeyHandle'] = '';
            $args['customTopicTreeNodeID'] = 0;
        }

        if ($args['rss']) {
            $pf = null;
            if (isset($this->pfID) && $this->pfID) {
                $pf = Feed::getByID($this->pfID);
            }

            if (!is_object($pf)) {
                $pf = new \Concrete\Core\Entity\Page\Feed();
                $pf->setTitle($args['rssTitle']);
                $pf->setDescription($args['rssDescription']);
                $pf->setHandle($args['rssHandle']);
            }

            $pf->setParentID($args['cParentID']);
            $pf->setPageTypeID($args['ptID']);
            $pf->setIncludeAllDescendents($args['includeAllDescendents']);
            $pf->setDisplayAliases($args['displayAliases']);
            $pf->setDisplayFeaturedOnly($args['displayFeaturedOnly']);
            $pf->setDisplaySystemPages($args['displaySystemPages']);
            $pf->displayShortDescriptionContent();
            $pf->save();
            $args['pfID'] = $pf->getID();
        } elseif (isset($this->pfID) && $this->pfID && !$args['rss']) {
            // let's make sure this isn't in use elsewhere.
            $cnt = $db->fetchColumn('select count(pfID) from btPageList where pfID = ?', [$this->pfID]);
            if ($cnt == 1) { // this is the last one, so we delete
                $pf = Feed::getByID($this->pfID);
                if (is_object($pf)) {
                    $pf->delete();
                }
            }
            $args['pfID'] = 0;
        }

        if ($args['filterDateOption'] != 'between') {
            $args['filterDateStart'] = null;
            $args['filterDateEnd'] = null;
        }

        if ($args['filterDateOption'] == 'past') {
            $args['filterDateDays'] = $args['filterDatePast'];
        } elseif ($args['filterDateOption'] == 'future') {
            $args['filterDateDays'] = $args['filterDateFuture'];
        } else {
            $args['filterDateDays'] = null;
        }

        $args['pfID'] = (int) ($args['pfID']);
        parent::save($args);
    }

    public function isBlockEmpty()
    {
        $pages = $this->get('pages');
        if (isset($this->pageListTitle) && $this->pageListTitle) {
            return false;
        }
        if (empty($pages)) {
            if ($this->noResultsMessage) {
                return false;
            }

            return true;
        }
        if ($this->includeName || $this->includeDate || $this->displayThumbnail
                || $this->includeDescription || $this->useButtonForLink
            ) {
            return false;
        }

        return true;
    }

    public function cacheBlockOutput()
    {
        if ($this->btCacheBlockOutput === null) {
            if (!$this->enableExternalFiltering && !$this->paginate) {
                $this->btCacheBlockOutput = true;
            } else {
                $this->btCacheBlockOutput = false;
            }
        }

        return  $this->btCacheBlockOutput;
    }

    protected function loadKeys()
    {
        $attributeKeys = [];
        $keys = CollectionKey::getList();
        foreach ($keys as $ak) {
            if ($ak->getAttributeTypeHandle() == 'topics') {
                $attributeKeys[] = $ak;
            }
        }
        $this->set('attributeKeys', $attributeKeys);
    }

    /**
     * @return string the reason why the attribute key is not usable (or an empty string if it's usable)
     */
    protected function checkSearchablePageAttributeKey(string $handle): string
    {
        $category = $this->app->make(PageCategory::class);
        $key = $category->getAttributeKeyByHandle($handle);
        if ($key === null) {
            return t('You must create the %s page attribute first.', "<code>{$handle}</code>");
        }
        if (!$key->isAttributeKeyContentIndexed()) {
            return t('The %s page attribute must be indexed.', "<code>{$handle}</code>");
        }
        
        return '';
    }
}
