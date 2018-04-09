<?php
namespace Concrete\Attribute\Express;

use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Attribute\Controller as AttributeTypeController;
use Concrete\Core\Entity\Attribute\Key\Settings\ExpressSettings;
use Concrete\Core\Entity\Attribute\Value\Value\ExpressValue;
use Concrete\Core\Error\ErrorList\Error\Error;
use Concrete\Core\Error\ErrorList\Field\AttributeField;

class Controller extends AttributeTypeController
{
    public $helpers = ['form'];

    protected $searchIndexFieldDefinition = ['type' => 'integer', 'options' => ['notnull' => false]];

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('database');
    }

    public function getAttributeValueClass()
    {
        return ExpressValue::class;
    }

    public function saveKey($data)
    {
        /**
         * @var ExpressType
         */
        $type = $this->getAttributeKeySettings();
        $id = $data['exEntityID'];
        $entity = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entity')
            ->findOneById($id);
        if (is_object($entity)) {
            $type->setEntity($entity);
        }

        return $type;
    }

    public function type_form()
    {
        $this->load();
    }

    public function form()
    {
        $entry = null;
        if ($this->attributeValue) {
            $value = $this->attributeValue->getValueObject();
            if (is_object($value)) {
                $entry = $value->getSelectedEntries()[0];
            }
        }
        $entrySelector = $this->app->make('form/express/entry_selector');
        $this->set('entrySelector', $entrySelector);
        $this->set('entry', $entry);
        $this->set('entity', $this->getEntity());
    }

    public function searchForm($list)
    {
        $list->filterByAttribute($this->attributeKey->getAttributeKeyHandle(), '%' . $this->request('value') . '%', 'like');

        return $list;
    }

    public function getSearchIndexValue()
    {
        $o = $this->attributeValue;
        if (is_object($o)) {
            $e = $o->getValue()->getSelectedEntries()[0];
            if (is_object($e)) {
                return $e->getID();
            }
        }
    }

    public function createAttributeValue($entry)
    {
        $selected = [];
        if (!is_array($entry)) {
            $selected[] = $entry;
        } else {
            $selected = $entry;
        }
        $av = new ExpressValue();
        $av->setSelectedEntries($selected);

        return $av;
    }

    public function getDisplayValue()
    {
        $html = '';
        foreach ($this->attributeValue->getValue()->getSelectedEntries() as $entry) {
            $html .= '<div>';
            $entity = $entry->getEntity();
            $columns = $entity->getResultColumnSet();
            foreach ($columns->getColumns() as $column) {
                $html .= '<span>' . $column->getColumnValue($entry) . '</span>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function createAttributeValueFromRequest()
    {
        $data = $this->post();
        if (isset($data['value'])) {
            $entity = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entry')
                ->findOneById($data['value']);
        }

        if (isset($entity) && $entity) {
            return $this->createAttributeValue($entity);
        } else {
            return $this->createAttributeValue([]);
        }
    }

    protected function getEntity()
    {
        $type = $this->getAttributeKeySettings();
        if (is_object($type)) {
            return $type->getEntity();
        }
    }

    protected function load()
    {
        $entityID = 0;
        $entities = [];
        $entity = $this->getEntity();
        if (is_object($entity)) {
            $entityID = $entity->getID();
        }
        $r = $this->entityManager->getRepository('Concrete\Core\Entity\Express\Entity')
            ->findAll();
        foreach ($r as $entity) {
            $entities[$entity->getID()] = $entity->getName();
        }
        $this->set('entityID', $entityID);
        $this->set('entities', $entities);
    }

    public function getAttributeKeySettingsClass()
    {
        return ExpressSettings::class;
    }

    public function validateForm($data)
    {
        $required = $this->getAttributeKey()->getAkIsRequired();
        $value = $data['value']->getValue()->getSelectedEntries();

        if (!$required) {
            return true;
        } elseif ($required && !count($value)) {
            return new Error(t('You must specify a valid a express entity for attribute %s', $this->getAttributeKey()
                ->getAttributeKeyDisplayName()),
                new AttributeField($this->getAttributeKey())
            );
        }

        return true;
    }
}
