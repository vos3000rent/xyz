<?php
namespace Concrete\Core\Page\Type\Validator;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Composer\Control\Control;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Support\Facade\Application;

class StandardValidator implements ValidatorInterface
{
    public function setPageTypeObject(Type $type)
    {
        $this->type = $type;
    }

    public function getPageTypeObject()
    {
        return $this->type;
    }

    public function validateCreateDraftRequest($template)
    {
        $app = Application::getFacadeApplication();
        $e = $app->make('error');
        $availablePageTemplates = $this->type->getPageTypePageTemplateObjects();
        $availablePageTemplateIDs = [];
        foreach ($availablePageTemplates as $ppt) {
            $availablePageTemplateIDs[] = $ppt->getPageTemplateID();
        }
        if (!is_object($template)) {
            $e->add(t('You must choose a page template.'));
        } else {
            if (!in_array($template->getPageTemplateID(), $availablePageTemplateIDs)) {
                $e->add(t('This page template is not a valid template for this page type.'));
            }
        }

        return $e;
    }

    public function validatePublishLocationRequest(Page $target = null, Page $page = null)
    {
        $app = Application::getFacadeApplication();
        $e = $app->make('error');
        if (!is_object($target) || $target->isError()) {
            if (!is_object($page) || !$page->isHomePage()) {
                $e->add(t('You must choose a page to publish this page beneath.'));
            }
        } else {
            $ppc = new \Permissions($target);
            if (!$ppc->canAddSubCollection($this->getPageTypeObject())) {
                $e->add(t('You do not have permission to publish a page in this location.'));
            }
        }

        return $e;
    }

    public function validatePublishDraftRequest(Page $page = null)
    {
        $app = Application::getFacadeApplication();
        $e = $app->make('error');
        $controls = Control::getList($this->type);
        foreach ($controls as $oc) {
            if (is_object($page)) {
                $oc->setPageObject($page);
            }
                $r = $oc->validate();
                if ($r instanceof ErrorList) {
                    $e->add($r);
                }
            }
        }

        return $e;
    }
}
