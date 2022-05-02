<?php

namespace Fromholdio\Elemental\Base\Model;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\SiteConfig\SiteConfig;
use Fromholdio\Elemental\Base\BetterElementTrait;

class ElementShared extends BetterBaseElement
{
    use BetterElementTrait;

    private static $table_name = 'ElementShared';
    private static $singular_name = 'Shared element';
    private static $plural_name = 'Shared elements';
    private static $description = 'Use to include an element in more than one area';

    private static $has_one = [
        'SourceArea' => BetterElementalArea::class,
        'SourceElement' => BaseElement::class
    ];


    public function getAvailableElements(): ArrayList
    {
        $elements = ArrayList::create();
        $siteConfig = SiteConfig::current_site_config();
        $area = $siteConfig->getElementalArea('SharedElementsArea');
        if ($area) {
            $elements = $area->getAllElements();
        }
        return $elements;
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'SourceAreaID',
            'SourceElementID'
        ]);

        $availableElements = $this->getAvailableElements();
        if ($availableElements->count() < 1)
        {
            $elementField = ReadonlyField::create(
                'NoSharedElementsField',
                $this->fieldLabel('SourceElement'),
                'There are no elements ready for sharing on this site'
            );
        }
        else {
            $elementSource = [];
            foreach ($availableElements as $availableElement) {
                $elementSource[$availableElement->ID] = $availableElement->getName();
            }
            $elementField = DropdownField::create(
                'SourceElementID',
                $this->fieldLabel('SourceElement'),
                $elementSource
            );
            $elementField->setHasEmptyDefault(true);
            $elementField->setEmptyString('- Select block -');
        }

        $fields->addFieldToTab(
            'Root.Main',
            $elementField
        );

        return $fields;
    }


    public function getSourceArea(): ?BetterElementalArea
    {
        $area = null;
        $id = (int) $this->getField('SourceAreaID');
        if ($id > 0) {
            /** @var BetterElementalArea $area */
            $area = BetterElementalArea::get()->find('ID', $id);
        }
        return $area;
    }

    public function getSourceElement(): ?BaseElement
    {
        $element = null;
        $area = $this->getSourceArea();
        $id = (int) $this->getField('SourceElementID');
        if (!is_null($area) && $id > 0) {
            $element = $area->getElementByID($id);
        }
        return $element;
    }


    public function isElementEmpty(): bool
    {
        $source = $this->getSourceElement();
        return is_null($source) || $source->isElementEmpty();
    }

    public function getElementByID(int $id): ?BaseElement
    {
        $sourceElement = $this->getSourceElement();
        return !is_null($sourceElement) && (int) $sourceElement->getField('ID') === $id
            ? $sourceElement
            : null;
    }

    public function provideElements(): ?SS_List
    {
        $element = $this->getSourceElement();
        return is_null($element) ? null : ArrayList::create($element);
    }
}
