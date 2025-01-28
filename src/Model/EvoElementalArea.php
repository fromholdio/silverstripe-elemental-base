<?php

namespace Fromholdio\Elemental\Base\Model;

use DNADesign\Elemental\Forms\ElementalAreaField;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer;

/**
 * @mixin Versioned
 */
class EvoElementalArea extends ElementalArea
{
    private static $table_name = 'EvoElementalArea';

    private static $element_classes = [
        'allowed' => [],
        'disallowed' => [],
        'do_sort_alphabetically' => true,
        'do_stop_inherit' => false
    ];

    private static $is_menu_visibility_enabled = true;

    private static $is_anchors_enabled = true;

    private static $has_one = [
        'ParentContainer' => DataObject::class
    ];


    /**
     * Cached data
     * ----------------------------------------------------
     */

    protected ?DataObject $currentContainer = null;
    protected ?string $currentName = null;

    protected ?DataObject $localContainer = null;
    protected ?string $localName = null;
    protected ?SS_List $localElements = null;

    protected ?ArrayList $allElements = null;
    protected ?ArrayList $elements = null;

    protected ?ArrayList $allMenuElements = null;
    protected ?ArrayList $menuElements = null;

    protected ?ArrayList $allElementControllers = null;
    protected ?ArrayList $elementControllers = null;

    protected array $elementsByID = [];

    protected ?EvoElementalArea $topArea = null;
    protected ?DataObject $topContainer = null;
    protected ?SiteTree $topPage = null;


    /**
     * Area Name
     * ----------------------------------------------------
     */

    public function getName(): ?string
    {
        return $this->getCurrentName() ?? $this->getLocalName();
    }

    public function getLocalName(): ?string
    {
        $name = $this->localName;
        if (!empty($name)) {
            return $name;
        }
        $container = $this->getLocalContainer();
        if (!is_null($container)) {
            $name = $container->getElementalAreaNameByID((int) $this->ID);
        }
        $this->localName = $name;
        return $name;
    }

    public function setCurrentName(?string $name): self
    {
        $this->currentName = $name;
        return $this;
    }

    public function getCurrentName(): ?string
    {
        return $this->currentName;
    }

    public function getURLSegment(): ?string
    {
        $urlSegment = null;
        $name = $this->getName();
        $container = $this->getContainer();
        if (!empty($name) && !empty($container)) {
            $urlSegment = $container->getElementalAreaURLSegment($name);
        }
        return $urlSegment;
    }

    public function getRelationName(): ?string
    {
        $relationName = null;
        $name = $this->getName();
        $container = $this->getContainer();
        if (!empty($name) && !empty($container)) {
            $relationName = $container->getElementalAreaRelationName($name);
        }
        return $relationName;
    }


    /**
     * Area Container
     * ----------------------------------------------------
     */

    /**
     * @return DataObject&ElementalAreasContainer|null
     */
    public function getContainer(): ?DataObject
    {
        return $this->getCurrentContainer() ?? $this->getLocalContainer();
    }

    public function setLocalContainer(DataObject $container): self
    {
        if (!$container->isInDB()) {
            throw new \LogicException(
                'The container object must be saved to the db before being '
                . 'set as the EvoElementalArea\'s container.'
            );
        }

        if (!$container::has_extension(ElementalAreasContainer::class)) {
            throw new \LogicException(
                'The container object must be extended by ' . ElementalAreasContainer::class
                . ' to be set as the EvoElementalArea\'s container.'
            );
        }

        $this->setField('ParentContainerClass', $container->getField('ClassName'));
        $this->setField('ParentContainerID', $container->getField('ID'));
        $this->localContainer = $container;
        return $this;
    }

    /**
     * @param bool $doUseCache
     * @return DataObject&ElementalAreasContainer|null
     */
    public function getLocalContainer(bool $doUseCache = true): ?DataObject
    {
        if (!$this->isInDB()) {
            return null;
        }

        if ($doUseCache) {
            $container = $this->localContainer;
            if (!is_null($container)) {
                return $container;
            }
        }

        $containerClass = $this->getField('ParentContainerClass');
        $containerID = (int) $this->getField('ParentContainerID');
        if (
            empty($containerClass)
            || $containerID < 1
            || !ClassInfo::exists($containerClass)
        ) {
            return null;
        }

        /** @var DataObject&ElementalAreasContainer $container */
        $container = $containerClass::get_by_id($containerID);
        if (is_null($container)) {
            return null;
        }

        if (!$container::has_extension(ElementalAreasContainer::class)) {
            return null;
        }

        if (!$container->hasLocalElementalAreaByID((int) $this->getField('ID'))) {
            return null;
        }

        $this->localContainer = $container;
        return $container;
    }

    public function setCurrentContainer(DataObject $container): self
    {
        $this->currentContainer = $container;
        return $this;
    }

    public function getCurrentContainer(): ?DataObject
    {
        return $this->currentContainer;
    }


    /**
     * Anchors
     * ----------------------------------------------------
     */

    public function isAnchorsEnabled(): bool
    {
        $isEnabled = static::config()->get('is_anchors_enabled');
        $this->getOwner()->invokeWithExtensions('updateIsAnchorsEnabled', $isEnabled);
        return $isEnabled;
    }

    public function getAllAnchorsInArea(): ?array
    {
        if (!$this->isAnchorsEnabled()) {
            return null;
        }
        $elements = $this->getAllElements();
        return $this->getAnchorsFromElements($elements);
    }

    public function getAnchorsInArea(): ?array
    {
        if (!$this->isAnchorsEnabled()) {
            return null;
        }
        $elements = $this->getElements();
        return $this->getAnchorsFromElements($elements);
    }

    protected function getAnchorsFromElements(SS_List $elements): ?array
    {
        $anchors = [];
        foreach ($elements as $element)
        {
            $elementAnchor = $element->getLocalAnchor();
            if (!empty($elementAnchor)) {
                $anchors[] = $elementAnchor;
            }
            $anchorsInElement = $element->getAnchorsInElement();
            if (empty($anchorsInElement)) continue;
            foreach ($anchorsInElement as $anchor) {
                $anchors[] = $anchor;
            }
        }
        return empty($anchors) ? null : $anchors;
    }


    /**
     * Element lists
     * ----------------------------------------------------
     */

    public function isAreaEmpty(): bool
    {
        return $this->getElements()->count() < 1;
    }

    public function getAllLocalElements(bool $doUseCache = true): SS_List
    {
        if ($doUseCache) {
            $elements = $this->localElements;
            if (!is_null($elements)) {
                return $elements;
            }
        }
        $elements = $this->getComponents('Elements');
        $this->localElements = $elements;
        return $elements;
    }

    public function getAllElements(): ArrayList
    {
        $elements = $this->allElements;
        if (!is_null($elements)) {
            return $elements;
        }
        $elements = ArrayList::create();
        $localElements = $this->getAllLocalElements();
        $configuredClasses = [];
        foreach ($localElements as $localElement)
        {
            $localElementClass = get_class($localElement);
            if (
                !isset($configuredClasses[$localElementClass])
                && !$localElement->isEvoElementalConfigured()
            ) {
                throw new \LogicException(
                    $localElementClass . ' is not properly configured to work '
                    . 'with the EvoElemental extensions. All Element classes require '
                    . 'the EvoElement trait applied (or BaseElement::isEvoElementalConfigured() '
                    . 'needs to return true, if you have applied the same interface/features differently).'
                );
            }
            $configuredClasses[$localElementClass] = true;
            $providedElements = $localElement->provideElements();
            if (is_null($providedElements)) {
                $localElement->setCurrentArea($this);
                $elements->push($localElement);
            }
            else {
                foreach ($providedElements as $providedElement) {
                    $providedElement->setCurrentArea($this);
                    $providedElement->setProviderElement($localElement);
                    $elements->push($providedElement);
                }
            }
        }
        $this->allElements = $elements;
        return $elements;
    }

    public function getElements(): ArrayList
    {
        $elements = $this->elements;
        if (!is_null($elements)) {
            return $elements;
        }
        $elements = ArrayList::create();
        $allElements = $this->getAllElements();
        foreach ($allElements as $element)
        {
            // canView checks Stage, and isElementEmpty
            if ($element->canView()) {
                $elements->push($element);
            }
        }
        $this->setExtraDataOnElements($elements);
        $this->elements = $elements;
        return $elements;
    }

    protected function setExtraDataOnElements(SS_List $elements): void
    {
        $totalElements = $elements->count();
        if ($totalElements < 1) {
            return;
        }
        $counter = 1;
        foreach ($elements as $element)
        {
            $data = [
                'First' => $counter === 1,
                'Last' => $counter === $totalElements,
                'TotalItems' => $totalElements,
                'Pos' => $counter,
                'EvenOdd' => $counter % 2 ? 'odd' : 'even'
            ];
            $element->setExtraData($data);
            $counter++;
        }
    }


    /**
     * Elements visible in Menus
     * ----------------------------------------------------
     * Intended for use by parent container for something
     * like an On This Page menu.
     */

    public function isMenuVisibilityEnabled(): bool
    {
        $isEnabled = (bool) static::config()->get('is_menu_visibility_enabled');
        $this->extend('updateIsMenuVisibilityEnabled', $isEnabled);
        return $isEnabled;
    }

    public function getAllMenuElements(): ArrayList
    {
        $menuElements = $this->allMenuElements;
        if (!is_null($menuElements)) {
            return $menuElements;
        }
        if (!$this->isMenuVisibilityEnabled()) {
            return ArrayList::create();
        }
        $elements = $this->getAllElements();
        $menuElements = $this->getMenuElementsFromElements($elements);
        $this->allMenuElements = $menuElements;
        return $menuElements;
    }

    public function getMenuElements(): ArrayList
    {
        $menuElements = $this->menuElements;
        if (!is_null($menuElements)) {
            return $menuElements;
        }
        if (!$this->isMenuVisibilityEnabled()) {
            return ArrayList::create();
        }
        $elements = $this->getElements();
        $menuElements = $this->getMenuElementsFromElements($elements);
        $this->menuElements = $menuElements;
        return $menuElements;
    }

    protected function getMenuElementsFromElements(SS_List $elements): ArrayList
    {
        $menuElements = ArrayList::create();
        foreach ($elements as $element) {
            if ($element->isVisibleInMenus()) {
                $menuElements->push($element);
            }
        }
        return $menuElements;
    }


    /**
     * Element Controllers
     * ----------------------------------------------------
     */

    public function getAllElementControllers(): ArrayList
    {
        $controllers = $this->allElementControllers;
        if (!is_null($controllers)) {
            return $controllers;
        }
        $elements = $this->getAllElements();
        $controllers = $this->getControllersFromElements($elements);
        $this->allElementControllers = $controllers;
        return $controllers;
    }

    public function getElementControllers(): ArrayList
    {
        $controllers = $this->elementControllers;
        if (!is_null($controllers)) {
            return $controllers;
        }
        $elements = $this->getElements();
        $controllers = $this->getControllersFromElements($elements);
        $this->elementControllers = $controllers;
        return $controllers;
    }

    protected function getControllersFromElements(SS_List $elements): ArrayList
    {
        $controllers = ArrayList::create();
        foreach ($elements as $element) {
            $controller = $element->getController();
            $controllers->push($controller);
        }
        return $controllers;
    }


    /**
     * Individual Elements
     * ----------------------------------------------------
     * Elements are cached per ID to local property
     * ---
     * First, check for matching Element ID within local
     *   has_one relation list.
     * Second, loop list of current Elements
     *  - if Element provides other Elements, check these
     *    for a matching ID (eg. virtual/shared elements).
     *  - if Element is extended by ElementalAreasContainer,
     *    (ie. it is also a Container), check it for matching
     *    ID within its Areas via container->getElementByID()
     *    (eg. ElementList/Group/s).
     */

    protected function doCacheElementByID(int $id, ?BaseElement $element): ?BaseElement
    {
        if (is_null($element)) {
            unset($this->elementsByID[$id]);
        } else {
            $this->elementsByID[$id] = $element;
        }
        return $element;
    }

    public function getElementByID(int $id): ?BaseElement
    {
        $cachedElementsArray = $this->elementsByID;
        $element = $cachedElementsArray[$id] ?? null;
        if (!is_null($element)) {
            return $element;
        }

        $localElements = $this->getAllLocalElements();
        $element = $localElements->find('ID', $id);
        if (!is_null($element)) {
            return $this->doCacheElementByID($id, $element);
        }

        $areaElements = $this->getAllElements();
        foreach ($areaElements as $areaElement)
        {
            if ($areaElement->ID === $id) {
                $element = $areaElement;
                break;
            }
            $providedElements = $areaElement->provideElements();
            if (!is_null($providedElements)) {
                $element = $providedElements->find('ID', $id);
                if (!is_null($element)) {
                    break;
                }
            }
            if ($areaElement::has_extension(ElementalAreasContainer::class, false)) {
                $element = $areaElement->getElementByID($id);
                if (!is_null($element)) {
                    break;
                }
            }
        }
        return $this->doCacheElementByID($id, $element);
    }


    /**
     * Elemental hierarchy helpers
     * ----------------------------------------------------
     * Values are all cached to local properties
     * --
     * TopArea: The farthest-most ElementalArea in the hierarchy above this Area.
     *   Retrieved via parent container, returns this Area if no container or container does
     *   not have an Area above it.
     * TopContainer: The farthest-most ElementalAreasContainer in the hierarchy.
     *   Retrieves parent Container of TopArea.
     * TopPage: If TopContainer is a SiteTree subclass, it returned here.
     * --
     * All of these traverse the hierarchy of the current instance, that is,
     * Current Areas will be used over Local Areas where they exist.
     */

    public function getTopArea(): self
    {
        $topArea = $this->topArea;
        if (!is_null($topArea)) {
            return $topArea;
        }
        $container = $this->getContainer();
        if (!is_null($container)) {
            $topArea = $container->getElementalTopArea();
        }
        $topArea = is_null($topArea) ? $this : $topArea;
        $this->topArea = $topArea;
        return $topArea;
    }

    /**
     * @return DataObject&ElementalAreasContainer|null
     */
    public function getTopContainer(): ?DataObject
    {
        $topContainer = $this->topContainer;
        if (!is_null($topContainer)) {
            return $topContainer;
        }
        $topArea = $this->getTopArea();
        $topContainer = $topArea->getContainer();
        $this->topContainer = $topContainer;
        return $topContainer;
    }

    public function getTopPage(): ?SiteTree
    {
        $topPage = $this->topPage;
        if (!is_null($topPage)) {
            return $topPage;
        }
        $container = $this->getTopContainer();
        $topPage = is_null($container) ? null : $container->getElementalTopPage();
        $this->topPage = $topPage;
        return $topPage;
    }


    /**
     * Templates/Rendering
     * ----------------------------------------------------
     * Areas:
     *  - {AreaClassName}_{AreaName}_{suffix}
     *  - {AreaClassName}_{suffix}
     *  - To and including EvoElementalArea
     */

    public function getRenderTemplates(string $suffix = ''): array
    {
        $classes = ClassInfo::ancestry($this->getField('ClassName'));
        $classes = array_reverse($classes);
        $baseClass = self::class;
        $templates = [];
        $classTemplates = [];
        $name = $this->getName();
        foreach ($classes as $key => $class)
        {
            if (!empty($name)) {
                $classTemplates[$class][] = $class . '_' . $name . $suffix;
            }
            $classTemplates[$class][] = $class . $suffix;
            if ($class === $baseClass) {
                break;
            }
        }
        $this->extend('updateRenderTemplates', $templates, $suffix);
        foreach ($classTemplates as $class => $variations) {
            $templates = [...$templates, ...$variations];
        }
        return $templates;
    }

    public function forTemplate(): ?DBHTMLText
    {
        $templates = $this->getRenderTemplates();
        return empty($templates) ? null : $this->renderWith($templates);
    }


    /**
     * Valid/Allowed Element Classes
     * ----------------------------------------------------
     */

    public static function parseElementClassesConfig(array $config, ?array $uninheritedConfig = null): array
    {
        $doStopInherit = $config['do_stop_inherit'] ?? false;
        if ($doStopInherit)
        {
            $config['allowed'] = [];
            $config['disallowed'] = [];
            if (!empty($uninheritedConfig))
            {
                $allowed = $uninheritedConfig['allowed'] ?? [];
                if (!empty($allowed)) {
                    $config['allowed'] = $allowed;
                }
                $disallowed = $uninheritedConfig['disallowed'] ?? [];
                if (!empty($disallowed)) {
                    $config['disallowed'] = $disallowed;
                }
            }
        }
        return $config;
    }

    protected function getElementClassesConfig(): array
    {
        $config = static::config()->get('element_classes');
        $uninheritedConfig = static::config()->get('element_classes', Config::UNINHERITED);
        return static::parseElementClassesConfig($config, $uninheritedConfig);
    }

    public function getAllowedElementClasses(): array
    {
        $classes = $this->getElementClassesConfig()['allowed'] ?? [];
        $classes = array_values(array_filter($classes));
        $container = $this->getContainer();
        $name = $this->getName();
        if (!is_null($container) && !is_null($name))
        {
            $containerConfig = $container->getElementalAreaElementClassesConfig($name);
            $containerClasses = $containerConfig['allowed'] ?? [];
            $containerClasses = array_values(array_filter($containerClasses));
            if (!empty($containerClasses))
            {
                $classes = empty($classes)
                    ? $containerClasses
                    : array_intersect($classes, $containerClasses);
            }
        }
        return $classes;
    }

    public function getDisallowedElementClasses(): array
    {
        $classes = $this->getElementClassesConfig()['disallowed'] ?? [];
        $classes = array_values(array_filter($classes));
        $container = $this->getContainer();
        $name = $this->getName();
        if (!is_null($container) && !is_null($name))
        {
            $containerConfig = $container->getElementalAreaElementClassesConfig($name);
            $containerClasses = $containerConfig['disallowed'] ?? [];
            $containerClasses = array_values(array_filter($containerClasses));
            if (!empty($containerClasses))
            {
                $classes = empty($classes)
                    ? $containerClasses
                    : [...$classes, ...$containerClasses];
            }
        }
        $classes[] = BaseElement::class;
        $classes[] = EvoBaseElement::class;
        return $classes;
    }

    public function getValidElementClasses(): array
    {
        $allowedClasses = $this->getAllowedElementClasses();
        $availableClasses = empty($allowedClasses)
            ? ClassInfo::subclassesFor(BaseElement::class)
            : $allowedClasses;

        $validClasses = [];
        $disallowedClasses = $this->getDisallowedElementClasses();
        foreach ($availableClasses as $availableClass)
        {
            $classInst = $availableClass::singleton();
            if (
                !in_array($availableClass, $disallowedClasses)
                && $classInst->isEvoElementalConfigured()
                && $classInst->canCreate()
            ) {
                $validClasses[$availableClass] = $classInst->getType();
            }
        }

        $isSortAlpha = $this->getElementClassesConfig()['do_sort_alphabetically'] ?? [];
        if ($isSortAlpha) {
            asort($validClasses);
        }

        if (isset($validClasses[BaseElement::class])) {
            unset($validClasses[BaseElement::class]);
        }

        $this->extend('updateValidElementClasses', $validClasses);
        return $validClasses;
    }


    /**
     * Data processing and validation methods
     * ----------------------------------------------------
     */

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        $this->parseParentContainerValues();
    }

    protected function parseParentContainerValues(): void
    {
        $containerClass = $this->getField('ParentContainerClass');
        $containerID = (int) $this->getField('ParentContainerID');
        if (empty($containerClass) || $containerID < 1) {
            $classValue = null;
            $idValue = 0;
        }
        else {
            $classValue = DataObject::getSchema()->baseDataClass($containerClass);
            $idValue = $containerID;
        }
        $this->setField('ParentContainerClass', $classValue);
        $this->setField('ParentContainerID', $idValue);
    }


    /**
     * CMS fields
     * ----------------------------------------------------
     */

    public function provideElementalAreaCMSFields(string $relationName): FieldList
    {
        $fields = FieldList::create(
            ElementalAreaField::create(
                $relationName,
                $this,
                $this->getValidElementClasses()
            )
        );
        $this->extend('updateElementalAreaFields', $fields, $relationName);
        return $fields;
    }


    /**
     * Permissions
     * ----------------------------------------------------
     */

    public function canView($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (is_bool($extended)) {
            return $extended;
        }
        $container = $this->getContainer();
        return is_null($container)
            ? (bool) Permission::check('CMS_ACCESS', 'any', $member)
            : $container->canView($member);
    }

    public function canEdit($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (is_bool($extended)) {
            return $extended;
        }
        $container = $this->getLocalContainer(false);
        return is_null($container)
            ? (bool) Permission::check('CMS_ACCESS', 'any', $member)
            : $container->canEdit($member);
    }

    public function canDelete($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (is_bool($extended)) {
            return $extended;
        }
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if (is_bool($extended)) {
            return $extended;
        }
        return $this->canEdit($member);
    }


    /**
     * Fixes/Overrides
     * ----------------------------------------------------
     */

    public function Breadcrumbs(): ?DBField
    {
        // Removes hardcoding of 'ElementalAreaID'
        $crumb = null;
        $container = $this->getContainer();
        if (!is_null($container)) {
            if ($container->hasMethod('CMSEditLink')) {
                $cmsEditLink = $container->CMSEditLink();
                if (!empty($cmsEditLink)) {
                    $crumb = DBField::create_field(
                        'HTMLVarchar',
                        '<a href="' . $cmsEditLink . '">' . $container->getTitle() . '</a>'
                    );
                }
            }
        }
        return $crumb;
    }

    public function Elements(): SS_List
    {
        return $this->getAllLocalElements(false);
    }

    public function ElementControllers(): ArrayList
    {
        return $this->getAllElementControllers();
    }

    public function getOwnerPage(): ?DataObject
    {
        return $this->getLocalContainer(false);
    }


    /**
     * No longer necessary
     * ----------------------------------------------------
     */

    public function supportedPageTypes(): array
    {
        return [];
    }

    public function setElementsCached(ArrayList $elements): ArrayList
    {
        return $elements;
    }

    public function setOwnerPageCached(DataObject $page): DataObject
    {
        return $page;
    }

}
