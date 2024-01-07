<?php

namespace Fromholdio\Elemental\Base\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use Fromholdio\Elemental\Base\Controllers\EvoElementController;

class ElementalContentControllerExtension extends Extension
{
    private static $handled_elemental_area_names;

    private static $url_handlers = [
        'area/$AreaURLSegment!/$ElementID!' => 'handleElement'
    ];

    private static $allowed_actions = [
        'handleElement'
    ];


    /**
     * Actions
     * ----------------------------------------------------
     */

    public function handleElement(): EvoElementController
    {
        $request = $this->getOwner()->getRequest();

        /** @var SiteTree&ElementalAreasContainer $page */
        $page = $this->getOwner()->data();
        if (!$page::has_extension(ElementalAreasContainer::class)) {
            return $this->getOwner()->httpError(404);
        }

        $elementID = (int) $request->param('ElementID');
        if ($elementID < 1) {
            return $this->getOwner()->httpError(404, 'No element ID supplied');
        }

        $areaURLSegment = $request->param('AreaURLSegment');
        if (empty($areaURLSegment)) {
            return $this->getOwner()->httpError(404, 'No area urlsegment supplied');
        }
        if ($areaURLSegment === 'all') {
            $areaURLSegment = null;
        }

        $handledAreaNames = $this->getOwner()->getHandledElementalAreaNames();
        if (empty($areaURLSegment)) {
            $element = $page->getElementByID($elementID, $handledAreaNames);
        }
        else {
            $area = $page->getElementalAreaByURLSegment($areaURLSegment);
            if (is_null($area)) {
                return $this->getOwner()->httpError(404, 'Invalid area ID supplied');
            }
            if (is_array($handledAreaNames) && !in_array($area->getName(), $handledAreaNames, true)) {
                return $this->getOwner()->httpError(404, 'Area not supported');
            }
            $element = $area->getElementByID($elementID);
        }

        if (is_null($element)) {
            return $this->getOwner()->httpError(404, 'Element not found');
        }

        $controller = $element->getController();
        if (!is_a($controller, EvoElementController::class)) {
            throw new \LogicException(
                get_class($element) . ' must provide a controller that '
                . 'is or subclasses EvoElementController.'
            );
        }
        return $controller;
    }


    /**
     * Handled Elemental Areas
     * ----------------------------------------------------
     * Previously, handleElement() loops over every ElementalArea relation
     * on the page (after finding them) in order to retrieve a matching
     * ElementController.
     * --
     * By providing $handled_elemental_area_names we can restrict the Areas
     * on the page that are traversed to those matching the supplied names.
     * --
     * Set to NULL to ignore this and loop all page Areas.
     * --
     * The value should be an array. It can be an associative array, with the
     * Area Names in the values. Keys with empty values will be discarded.
     * --
     * e.g. An Area may not be allowed to contain forms or any other elements
     * that require URL handling. In this case, they can be
     */

    public function getHandledElementalAreaNames(): ?array
    {
        $handledNames = null;
        $names = $this->getOwner()->config()->get('handled_elemental_area_names');
        if (!is_null($names))
        {
            $handledNames = [];
            foreach ($names as $name) {
                if (!empty($name)) {
                    $handledNames[] = $name;
                }
            }
        }
        return $handledNames;
    }
}
