<?php

namespace Fromholdio\Elemental\Base\Services;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Services\ElementTabProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;

class BetterElementTabProvider extends ElementTabProvider
{
    /**
     * Generate top level tab names for the given element class (and cache them)
     *
     * @param string $elementClass
     * @return array
     */
    protected function generateTabsForElement($elementClass)
    {
        // Create the specified element
        /** @var BaseElement $element */
        $element = Injector::inst()->create($elementClass);

        // Generate CMS fields and grab the "Root" tabset.
        /** @var TabSet $tabset */
        $tabset = $element->getInlineCMSFields()->fieldByName('Root');

        // Get and map the tab names/titles into an associative array
        $tabs = [];
        /** @var Tab $tabDefinition */
        foreach ($tabset->Tabs() as $tabDefinition) {
            $tabs[] = [
                'name' => $tabDefinition->getName(),
                'title' => $tabDefinition->Title(),
            ];
        }

        // Cache them for next time
        $this->getCache()->set($this->getCacheKey($elementClass), $tabs);

        return $tabs;
    }
}
