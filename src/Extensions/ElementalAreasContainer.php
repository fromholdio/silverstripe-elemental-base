<?php

namespace Fromholdio\Elemental\Base\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\CMSFieldsPlacement\CMSFieldsPlacement;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Fromholdio\Elemental\Base\Model\BetterElementalArea;

class ElementalAreasContainer extends DataExtension
{
    private static $has_many = [
        'ContainedAreas' => BetterElementalArea::class . '.ParentContainer'
    ];

    /**
     * Duplication assumes that you have setup $cascade_duplicates entries for each
     * of your elemental area relations. If you have not, then you will need to.
     * i.e. $has_one of 'ContentArea' on Page should be listed in $cascades_duplicates,
     * so that in the function below ->getLocalElementalAreas() returns the new cloned areas.
     */
    public function onAfterDuplicate(DataObject $original, bool $doWrite): void
    {
        $newContainerClass = get_class($this->getOwner());
        $newContainerID = $this->getOwner()->getField('ID');
        $newAreas = $this->getOwner()->getLocalElementalAreas();
        foreach ($newAreas as $newArea) {
            $newArea->setField('ParentContainerID', $newContainerID);
            $newArea->setField('ParentContainerClass', $newContainerClass);
            $newArea->write();
            $newArea->publishSingle();
        }
    }


    public function updateCMSActions(FieldList $actions)
    {
        $actions->push(
            $action = CustomAction::create('doPublishWithAreas', 'Publish (including all blocks)')
                ->setShouldRefresh(true)
                ->addExtraClass('btn-outline-primary')
                ->removeExtraClass('btn-info')
        );
        $action->setAttribute('style', 'margin-left:auto;');
    }

    public function doPublishWithAreas()
    {
        $this->getOwner()->publishRecursive();
        $this->getOwner()->doPublishLocalElementalAreas();
        return true;
    }

    public function doPublishLocalElementalAreas()
    {
        $areas = $this->getOwner()->getLocalElementalAreas();
        foreach ($areas as $area) {
            $area->publishRecursive();
        }
    }


    /**
     * TODO:
     *  - Summaries/Search/something
     */


    /**
     * Require default records
     * ----------------------------------------------------
     */

    public function requireDefaultRecords(): void
    {
        $fieldNames = $this->getOwner()->getElementalAreaFieldNames();
        if (empty($fieldNames)) {
            return;
        }

        $this->getOwner()->invokeWithExtensions('onBeforeRequireDefaultElementalAreas');

        foreach ($fieldNames as $fieldName) {
            $filterAny[$fieldName] = ['', 0, null];
        }

        /** @var DataObject&ElementalAreasContainer $containerClass */
        $containerClass = get_class($this->getOwner());

        $containersWithMissingAreas = $containerClass::get()->filterAny($filterAny);
        foreach ($containersWithMissingAreas as $container)
        {
            $container->requireLocalElementalAreas();
            $container->write();
            if (
                $container::has_extension(Versioned::class)
                && $container->isPublished()
            ) {
                $container->publishSingle();
            }
        }

        $this->getOwner()->invokeWithExtensions('onAfterRequireDefaultElementalAreas');
    }

    public function requireLocalElementalAreas(): void
    {
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $this->getOwner()->requireLocalElementalArea($name);
        }
    }

    public function requireLocalElementalArea(string $name): void
    {
        if (Versioned::get_stage() !== Versioned::DRAFT) {
            return;
        }
        if (!$this->getOwner()->isValidElementalAreaName($name)) {
            return;
        }

        $areaFieldName = $this->getOwner()->getElementalAreaFieldName($name);
        $areaClassName = $this->getOwner()->getElementalAreaClassName($name);
        if (empty($areaFieldName) || empty($areaClassName)) {
            return;
        }

        $area = null;
        $areaID = (int) $this->getOwner()->getField($areaFieldName);
        if ($areaID > 0) {
            $area = $areaClassName::get()->find('ID', $areaID);
        }
        if (is_null($area))
        {
            $existingArea = BetterElementalArea::get()->find('ID', $areaID);
            if (!is_null($existingArea))
            {
                try {
                    $existingArea->setField('ClassName', $areaClassName);
                    $existingArea->write();
                    $existingArea->publishSingle();
                    $area = $existingArea;
                }
                catch (\Throwable $throw) {
                    $existingArea->doArchive();
                }
            }
            if ($this->getOwner()->isInDB() && is_null($area)) {
                $area = $areaClassName::create();
                $area->setLocalContainer($this->getOwner());
                $area->write();
                $area->publishSingle();
                $this->getOwner()->setField($areaFieldName, $area->ID);
            }
        }
    }

    public function onBeforeWrite(): void
    {
        $this->getOwner()->doRequireLocalElementalAreas = !$this->getOwner()->isInDB();
    }

    public function onAfterWrite(): void
    {
        if ($this->getOwner()->doRequireLocalElementalAreas) {
            $this->getOwner()->requireLocalElementalAreas();
            $this->getOwner()->write();
        }
    }


    /**
     * Area configuration accessors
     * ----------------------------------------------------
     */

    public function getElementalAreasConfig(bool $onlyUninherited = false): array
    {
        $config = $onlyUninherited
            ? $this->getOwner()->config()->get('elemental_areas', Config::UNINHERITED)
            : $this->getOwner()->config()->get('elemental_areas');
        return empty($config) ? [] : $config;
    }

    public function getElementalAreaConfig(string $name, bool $onlyUninherited = false): ?array
    {
        $config = $this->getOwner()->getElementalAreasConfig($onlyUninherited)[$name] ?? null;
        return empty($config) ? null : $config;
    }

    public function getElementalAreaElementClassesConfig(string $name): ?array
    {
        $config = $this->getOwner()->getElementalAreaConfig($name)['elemental_areas'] ?? null;
        if (empty($config)) return null;
        $uninheritedConfig = $this->getOwner()->getElementalAreaConfig($name, true)['elemental_areas'] ?? null;
        return BetterElementalArea::parseElementClassesConfig($config, $uninheritedConfig);
    }


    /**
     * Area metadata: names, relation names, fields & classes
     * ----------------------------------------------------
     */

    public function isValidElementalAreaName(string $name): bool
    {
        $names = $this->getOwner()->getElementalAreaNames();
        return is_array($names) && in_array($name, $names);
    }

    public function getElementalAreaNames(): array
    {
        $config = $this->getOwner()->getElementalAreasConfig();
        return empty($config) ? [] : array_keys($config);
    }

    public function getElementalAreaNameByID(int $id): ?string
    {
        $areaName = null;
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name)
        {
            $fieldValue = $this->getOwner()->getLocalElementalAreaID($name);
            if ($fieldValue === $id) {
                $areaName = $name;
                break;
            }
        }
        return $areaName;
    }

    public function getElementalAreaRelationNames(): array
    {
        $relationNames = [];
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $config = $this->getOwner()->getElementalAreaConfig($name);
            $relationNames[$name] = $config['has_one'] ?? $name;
        }
        return $relationNames;
    }

    public function getElementalAreaRelationName(string $name): ?string
    {
        $relationNames = $this->getOwner()->getElementalAreaRelationNames();
        return $relationNames[$name] ?? null;
    }

    public function getElementalAreaFieldNames(): array
    {
        $fieldNames = [];
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $config = $this->getOwner()->getElementalAreaConfig($name);
            $relationName = $config['has_one'] ?? $name;
            $fieldNames[$name] = $relationName . 'ID';
        }
        return $fieldNames;
    }

    public function getElementalAreaFieldName(string $name): ?string
    {
        $fieldNames = $this->getOwner()->getElementalAreaFieldNames();
        return $fieldNames[$name] ?? null;
    }

    public function getElementalAreaClassName(string $name): ?string
    {
        $className = null;
        $relationName = $this->getOwner()->getElementalAreaRelationName($name);
        if (!empty($relationName)) {
            $className = $this->getOwner()->hasOne()[$relationName] ?? null;
        }
        return $className;
    }

    public function getElementalAreaURLSegment(string $name): ?string
    {
        $config = $this->getOwner()->getElementalAreaConfig($name);
        return $config['url_segment'] ?? null;
    }


    /**
     * Area accessors
     * ----------------------------------------------------
     */

    public function getElementalAreas(): ArrayList
    {
        $areas = ArrayList::create();
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $area = $this->getOwner()->getElementalArea($name);
            if (!is_null($area)) {
                $areas->push($area);
            }
        }
        return $areas;
    }

    public function getLocalElementalAreas(): ArrayList
    {
        $areas = ArrayList::create();
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $area = $this->getOwner()->getLocalElementalArea($name);
            if (!is_null($area)) {
                $areas->push($area);
            }
        }
        return $areas;
    }

    public function getElementalArea(string $name): ?BetterElementalArea
    {
        $area = $this->getOwner()->getCurrentElementalArea($name);
        if (is_null($area)) {
            $area = $this->getOwner()->getLocalElementalArea($name);
        }
        if (is_null($area)) {
            return null;
        }
        $area->setCurrentName($name);
        $area->setCurrentContainer($this->getOwner());
        return $area;
    }

    public function getElementalAreaByURLSegment(string $urlSegment): ?BetterElementalArea
    {
        $area = null;
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name)
        {
            $config = $this->getOwner()->getElementalAreaConfig($name);
            $areaURLSegment = $config['url_segment'] ?? null;
            if ($areaURLSegment === $urlSegment) {
                $area = $this->getOwner()->getElementalArea($name);
                break;
            }
        }
        return $area;
    }


    /**
     * Areas: Current vs Local
     * ----------------------------------------------------
     * Current: the area in use for this instance, could be an
     *   area that has been inherited from elsewhere.
     * Local: stored in this container's has_one relations
     */

    public function getCurrentElementalArea(string $name): ?BetterElementalArea
    {
        $area = null;
        if ($this->getOwner()->isValidElementalAreaName($name)) {
            $config = $this->getOwner()->getElementalAreaConfig($name);
            if (!is_null($config)) {
                $currentMethodName = $config['current'] ?? $name;
                $area = $this->getOwner()->{$currentMethodName}($name);
                if (!$area || !$area->exists() || !is_a($area, BetterElementalArea::class, false)) {
                    $area = null;
                }
            }
        }
        if (!is_null($area)) {
            $area->setCurrentContainer($this->getOwner(), $name);
        }
        return $area;
    }

    public function getLocalElementalArea(string $name): ?BetterElementalArea
    {
        $area = null;
        if ($this->getOwner()->isValidElementalAreaName($name)) {
            $config = $this->getOwner()->getElementalAreaConfig($name);
            if (!is_null($config)) {
                $hasOneName = $config['has_one'] ?? $name;
                $area = $this->getOwner()->getComponent($hasOneName);
                if (!$area || !$area->exists() || !is_a($area, BetterElementalArea::class, false)) {
                    $area = null;
                }
            }
        }
        return $area;
    }

    public function getLocalElementalAreaID(string $name): ?int
    {
        $value = null;
        $fieldName = $this->getOwner()->getElementalAreaFieldName($name);
        if (!empty($fieldName)) {
            $fieldValue = $this->getOwner()->getField($fieldName);
            if (!empty($fieldValue)) {
                $value = (int) $fieldValue;
            }
        }
        return $value;
    }

    public function hasLocalElementalAreaByID(int $id): bool
    {
        $name = $this->getOwner()->getElementalAreaNameByID($id);
        return !empty($name);
    }

    public function isElementalAreaEnabled(string $name): bool
    {
        $config = $this->getOwner()->getElementalAreaConfig($name);
        $isEnabled = $config['enabled'] ?? false;
        return $this->getOwner()->isValidElementalAreaName($name) && $isEnabled;
    }


    /**
     * Anchors
     * ----------------------------------------------------
     * TODO: this is messy and needs work. Least of all
     * these Title and Name fields is confusing.
     */

    public function getAnchorsInAreas(): array
    {
        $anchors = [];
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            $areaAnchors = $this->getOwner()->getAnchorsByAreaName($name);
            if (!empty($areaAnchors)) {
                $anchors = [...$anchors, ...$areaAnchors];
            }
        }
        return $anchors;
    }

    public function getAnchorsByAreaName(string $name): ?array
    {
        $area = $this->getOwner()->getElementalArea($name);
        return is_null($area) ? null : $area->getAllAnchorsInArea();
    }

    public function updateAnchorsOnPage(array &$anchors): void
    {
        $areaAnchors = $this->getOwner()->getAnchorsInAreas();
        if (!empty($areaAnchors)) {
            $anchors = [...$anchors, ...$areaAnchors];
        }
    }


    /**
     * Elemental hierarchy helpers
     * ----------------------------------------------------
     * TopArea: The farthest-most ElementalArea in the hierarchy above this container.
     *   Likely to be null unless this container is an Element nested inside an Area.
     * TopContainer: The farthest-most ElementalAreasContainer in the hierarchy. If this
     *   container is NOT inside an Area (ie. TopArea is null), then this container is
     *   the TopContainer.
     * TopPage: If TopContainer is a SiteTree subclass, it returned here.
     * --
     * All of these traverse the hierarchy of the current instance, that is,
     * CurrentElementalAreas will be used over LocalElementalAreas where they exist.
     */

    public function getElementalTopArea(): ?BetterElementalArea
    {
        return null;
    }

    /**
     * @return DataObject&ElementalAreasContainer|null
     */
    public function getElementalTopContainer(): ?DataObject
    {
        $topArea = $this->getOwner()->getElementalTopArea();
        return is_null($topArea)
            ? $this->getOwner()
            : $topArea->getTopContainer();
    }

    public function getElementalTopPage(): ?SiteTree
    {
        $container = $this->getOwner()->getElementalTopContainer();
        return !is_null($container) && is_a($container, SiteTree::class, false)
            ? $container
            : null;
    }

    public function getElementByID(int $id, ?array $areaNames = null): ?BaseElement
    {
        $element = null;
        $names = $this->getOwner()->getElementalAreaNames();
        foreach ($names as $name) {
            if (is_null($areaNames) || in_array($name, $areaNames, true))
            {
                $area = $this->getOwner()->getElementalArea($name);
                if (!is_null($area)) {
                    $element = $area->getElementByID($id);
                    if (!is_null($element)) {
                        break;
                    }
                }
            }
        }
        return $element;
    }


    /**
     * CMS fields
     * ----------------------------------------------------
     */

    public function updateCMSFields(FieldList $fields): void
    {
        $areas = $this->getOwner()->getLocalElementalAreas();
        /** @var BetterElementalArea $area */
        foreach ($areas as $area)
        {
            $areaName = $area->getName();
            if (empty($areaName)) continue;
            if (!$this->getOwner()->isElementalAreaEnabled($areaName)) continue;

            $cmsFieldsConfig = $this->getOwner()->getElementalAreaConfig($areaName)['cms_fields'] ?? null;
            if (empty($cmsFieldsConfig)) continue;

            $relationName = $this->getOwner()->getElementalAreaRelationName($areaName);
            if (empty($relationName)) continue;

            $areaFields = $area->provideElementalAreaCMSFields($relationName);
            if ($areaFields->count() < 1) continue;

            $fields = CMSFieldsPlacement::placeFields(
                $fields, $areaFields, $cmsFieldsConfig, $this->getOwner()
            );
        }
    }

    public function updateSiteCMSFields(FieldList $fields): void
    {
        $this->updateCMSFields($fields);
    }


    /**
     * @return DataObject&ElementalAreasContainer
     */
    public function getOwner(): DataObject
    {
        /** @var DataObject&ElementalAreasContainer $owner */
        $owner = parent::getOwner();
        return $owner;
    }
}
