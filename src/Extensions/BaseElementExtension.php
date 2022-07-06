<?php

namespace Fromholdio\Elemental\Base\Extensions;

use DNADesign\Elemental\Controllers\ElementalAreaController;
use DNADesign\Elemental\Forms\EditFormFactory;
use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\CheckboxFieldGroup\CheckboxFieldGroup;
use Fromholdio\Elemental\Base\Model\BetterBaseElement;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\GraphQL\Controller as GraphQLController;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use SilverStripe\View\Parsers\URLSegmentFilter;
use Fromholdio\Elemental\Base\BetterElementTrait;
use Fromholdio\Elemental\Base\Controllers\BetterElementController;
use Fromholdio\Elemental\Base\Model\BetterElementalArea;
use Fromholdio\Elemental\Base\CSSFramework\BootstrapCSSFramework;
use Fromholdio\Elemental\Base\CSSFramework\CSSFrameworkInterface;

/**
 * @mixin BetterElementTrait
 */
class BaseElementExtension extends DataExtension
{
    private static $controller_class = BetterElementController::class;

    private static $is_title_enabled = false;
    private static $is_title_required = false;

    private static $is_advanced_edit_enabled = true;
    private static $advanced_edit_instruction = 'to edit more settings.';

    private static $is_menu_visibility_enabled = true;
    private static $is_menu_visibility_forced = false;

    // false for side area, shared elements, for example.
    private static $is_anchors_enabled = false;
    private static $anchor_field_names = [];

    private static $is_cms_history_enabled = false;

    private static $holder_templates = [];

    private static $inline_editable = true;
    private static $displays_title_in_template = false;
    private static $disable_pretty_anchor_name = false;

    private static $is_grid_row = false;
    private static $grid_columns_count = 12;

    private static $db = [
        'CMSName' => 'Varchar',
        'AnchorName' => 'Varchar',
        'ShowInMenus' => 'Boolean',

        'SizeXS' => 'Int',
        'SizeSM' => 'Int',
        'SizeMD' => 'Int',
        'SizeLG' => 'Int',
        'SizeXL' => 'Int',
        'OffsetXS' => 'Int',
        'OffsetSM' => 'Int',
        'OffsetMD' => 'Int',
        'OffsetLG' => 'Int',
        'OffsetXL' => 'Int',
        'VisibilityXS' => 'Varchar(10)',
        'VisibilitySM' => 'Varchar(10)',
        'VisibilityMD' => 'Varchar(10)',
        'VisibilityLG' => 'Varchar(10)',
        'VisibilityXL' => 'Varchar(10)',
    ];

    private static $field_labels = [
        'Title' => 'Title',
        'CMSName' => 'Name',
        'AnchorName' => 'Anchor',
        'AdvancedEditButton' => 'Advanced edit',
        'ShowInMenusGroup' => 'Visibility',
        'ShowInMenus' => 'Show in on-page navigation'
    ];



    /**
     * TODO: Summaries/Search/something
     * ----------------------------------------------------
     */

    public function getContentSummary() {}


    /**
     * Title visibility & handling
     * ----------------------------------------------------
     */

    public function Title(): ?string
    {
        $curr = Controller::curr();
        return !is_null($curr) && is_a($curr, GraphQLController::class, false)
            ? $this->getOwner()->getInlineCMSTitle()
            : $this->getOwner()->getLocalTitle();
    }

    public function getTitle(): ?string
    {
        return $this->Title();
    }

    public function getLocalTitle(): ?string
    {
        $title = $this->getOwner()->getField('Title');
        if (empty($title)) $title = null;
        return $this->getOwner()->isTitleEnabled() ? $title : null;
    }

    public function isTitleEnabled(): bool
    {
        $isEnabled = $this->getOwner()->config()->get('is_title_enabled');
        $this->getOwner()->invokeWithExtensions('updateIsTitleEnabled', $isEnabled);
        return $isEnabled;
    }

    public function isTitleRequired(): bool
    {
        $isEnabled = $this->getOwner()->isTitleEnabled();
        if (!$isEnabled) {
            return false;
        }
        $isRequired = $this->getOwner()->config()->get('is_title_required');
        $this->getOwner()->invokeWithExtensions('updateIsTitleRequired', $isRequired);
        return $isRequired;
    }

    public function getDefaultTitle(): string
    {
        return 'Untitled ' . mb_strtolower($this->getOwner()->getType());
    }

    public function getTitleField(): FormField
    {
        $field = TextField::create('Title', $this->getOwner()->fieldLabel('Title'));
        $field
            ->setAttribute('placeholder', $this->getOwner()->getDefaultTitle())
            ->setSchemaData(['attributes' => ['placeholder' => $this->getOwner()->getDefaultTitle()]]);
        $this->getOwner()->invokeWithExtensions('updateTitleField', $field);
        return $field;
    }

    public function enforceTitleSettings(): void
    {
        $title = $this->getOwner()->getField('Title');
        if ($this->getOwner()->isTitleRequired()) {
            if (empty($title)) {
                $title = $this->getOwner()->getDefaultTitle();
            }
        }
        elseif (!$this->getOwner()->isTitleEnabled()) {
            $title = null;
        }
        $this->getOwner()->setField('Title', $title);
    }


    /**
     * CMS Name
     * ----------------------------------------------------
     * Used for identification of Elements by CMS editors on
     * elements that have no Title. Don't confuse the editor user
     * with using Title field for both scenarios.
     */

    public function getName(): ?string
    {
        $name = $this->getOwner()->getField('CMSName');
        $this->getOwner()->invokeWithExtensions('updateName', $name);
        if (empty($name)) {
            $name = 'Unamed ' . mb_strtolower($this->getOwner()->getType());
        }
        return $name;
    }

    public function getNameField(): FormField
    {
        return TextField::create('CMSName', $this->getOwner()->fieldLabel('CMSName'));
    }


    /**
     * Anchors
     * ----------------------------------------------------
     * TODO: this is messy and needs work. Least of all
     * these Title and Name fields is confusing.
     */

    public function isAnchorsEnabled(): bool
    {
        $isEnabled = $this->getOwner()->config()->get('is_anchors_enabled');
        $this->getOwner()->invokeWithExtensions('updateIsAnchorsEnabled', $isEnabled);
        return $isEnabled;
    }

    public function getAnchorTitle(): ?string
    {
        if (!$this->getOwner()->isAnchorsEnabled()) {
            return null;
        }
        $title = $this->getOwner()->getLocalAnchorTitle();
        return empty($title)
            ? $this->getOwner()->getDefaultAnchorTitle()
            : $title;
    }

    public function getLocalAnchorTitle(): ?string
    {
        if (!$this->getOwner()->isAnchorsEnabled()) {
            return null;
        }
        $title = $this->getOwner()->getField('AnchorName');
        if (empty($title)) {
            $title = $this->getOwner()->getLocalTitle();
        }
        if (!empty($title)) {
            $provider = $this->getOwner()->getProviderElement();
            if (!is_null($provider)) {
                $providerAnchorTitle = $provider->getLocalAnchorTitle();
                if (empty($providerAnchorTitle)) {
                    $providerAnchorTitle = $provider->getDefaultAnchorTitle();
                }
                $title = $providerAnchorTitle . '-' . $title;
            }
        }
        return empty($title) ? null : $title;
    }

    public function getLocalAnchor(): ?string
    {
        $anchor = null;
        $title = $this->getOwner()->getLocalAnchorTitle();
        if (!empty($title)) {
            $filter = URLSegmentFilter::create();
            $anchor = $filter->filter($title);
        }
        return $anchor;
    }

    public function getDefaultAnchorTitle(): string
    {
        $title = 'e' . $this->getOwner()->getField('ID');
        $provider = $this->getOwner()->getProviderElement();
        if (!is_null($provider)) {
            $providerAnchorTitle = $provider->getLocalAnchorTitle();
            if (empty($providerAnchorTitle)) {
                $providerAnchorTitle = $provider->getDefaultAnchorTitle();
            }
            $title = $providerAnchorTitle . '-' . $title;
        }
        return $title;
    }

    public function getAnchorNameField(): FormField
    {
        $field = TextField::create(
            'AnchorName',
            $this->getOwner()->fieldLabel('AnchorName')
        );
        $field
            ->setAttribute('placeholder', $this->getOwner()->getAnchor())
            ->setSchemaData(['attributes' => ['placeholder' => $this->getOwner()->getAnchor()]]);

        $description = 'Set an anchor name to enable linking to this block from other content areas.'
            . '<br>Special characters are automatically converted or removed.';
        if ($this->getOwner()->isTitleEnabled()) {
            $description .= '<br>If empty, an anchor name will be generated from the Title.';
        }
        $description = DBField::create_field('HTMLFragment', $description);
        $field->setDescription($description);
        return $field;
    }

    public function getAnchorFieldNames(): ?array
    {
        $fieldNames = $this->getOwner()->config()->get('anchor_field_names');
        return empty($fieldNames) ? null : $fieldNames;
    }

    public function getAnchorsFromFields(): ?array
    {
        $anchors = [];
        $fieldNames = $this->getOwner()->getAnchorFieldNames();
        $fieldTypes = [DBHTMLText::class, DBHTMLVarchar::class];
        foreach ($fieldNames as $fieldName)
        {
            $field = $this->getOwner()->dbObject($fieldName);
            if (!is_null($field) && in_array(get_class($field), $fieldTypes)) {
                $fieldAnchors = $field->getAnchors();
                if (!empty($fieldAnchors)) {
                    $anchors = [...$anchors, ...array_values($fieldAnchors)];
                }
            }
        }
        $this->getOwner()->invokeWithExtensions('updateAnchorsFromFields', $anchors);
        return empty($anchors) ? null : $anchors;
    }

    public function getAnchorsInElement(): ?array
    {
        if (!$this->getOwner()->isAnchorsEnabled()) {
            return null;
        }
        $anchors = $this->getAnchorsFromFields();
        $this->getOwner()->invokeWithExtensions('updateAnchorsInElement', $anchors);
        return $anchors;
    }

    public function enforceAnchorSettings(): void
    {
        $name = $this->getOwner()->getField('AnchorName');
        if (!empty($name)) {
            if ($this->getOwner()->isAnchorsEnabled()) {
                $filter = URLSegmentFilter::create();
                $name = $filter->filter($name);
            }
            else {
                $name = null;
            }
            $this->getOwner()->setField('AnchorName', $name);
        }
    }


    /**
     * Menu visibility & getters
     * ----------------------------------------------------
     */

    public function isMenuVisibilityEnabled(): bool
    {
        $isEnabled = (bool) $this->getOwner()->config()->get('is_menu_visibility_enabled');
        $this->getOwner()->invokeWithExtensions('updateIsMenuVisibilityEnabled', $isEnabled);
        return $isEnabled;
    }

    public function isMenuVisibilityForced(): bool
    {
        $isForced = (bool) $this->getOwner()->config()->get('is_menu_visibility_forced');
        $this->getOwner()->invokeWithExtensions('updateIsMenuVisibilityForced', $isForced);
        return $isForced;
    }

    public function isVisibleInMenus(): bool
    {
        $provider = $this->getOwner()->getProviderElement();
        return is_null($provider)
            ? $this->getOwner()->isMenuVisibilityEnabled()
            && (
                $this->getOwner()->isMenuVisibilityForced()
                || $this->getOwner()->getField('ShowInMenus')
            )
            : $provider->isVisibleInMenus();
    }

    public function getMenuVisibilityField(): FormField
    {
        return CheckboxFieldGroup::create(
            'ShowInMenus',
            $this->getOwner()->fieldLabel('ShowInMenus'),
            $this->getOwner()->fieldLabel('ShowInMenusGroup')
        );
    }

    public function getAllMenusElements(): ArrayList
    {
        $elements = ArrayList::create();
        if (!$this->isMenuVisibilityEnabled()) {
            return $elements;
        }
        if ($this->getOwner()->isElementalAreasContainer())
        {
            /** @var ArrayList&BetterElementalArea[] $areas */
            $areas = $this->getOwner()->getElementalAreas();
            foreach ($areas as $area) {
                $areaElements = $area->getAllMenuElements();
                foreach ($areaElements as $areaElement) {
                    $elements->push($areaElement);
                }
            }
        }
        return $elements;
    }

    public function getMenuElements(): ArrayList
    {
        $elements = ArrayList::create();
        if (!$this->isMenuVisibilityEnabled()) {
            return $elements;
        }
        if ($this->getOwner()->isElementalAreasContainer())
        {
            /** @var ArrayList&BetterElementalArea[] $areas */
            $areas = $this->getOwner()->getElementalAreas();
            foreach ($areas as $area) {
                $areaElements = $area->getMenuElements();
                foreach ($areaElements as $areaElement) {
                    $elements->push($areaElement);
                }
            }
        }
        return $elements;
    }


    /**
     * Templates/Rendering
     * ----------------------------------------------------
     * Elements:
     *  - {ElementClassName}_{AreaName}_{suffix}
     *  - {ElementClassName}_{suffix}
     *  - To and including BaseElement
     * Holders:
     *  - Supply specific template/s via config $holder_templates
     *  - Else same as template stack with "_holder" as suffix
     */

    public function getHolderTemplates(): array
    {
        $templates = $this->getOwner()->config()->get('holder_templates');
        if (!empty($templates)) {
            if (is_string($templates)) {
                $templates = [$templates];
            }
            elseif (!is_array($templates)) {
                $templates = null;
            }
        }
        if (empty($templates)) {
            $templates = $this->getOwner()->getRenderTemplates('_holder');
        }
        else {
            $templates = array_filter($templates);
        }
        $this->getOwner()->extend('updateHolderTemplates', $templates);
        return $templates;
    }

    public function updateRenderTemplates(array &$templates, string $suffix)
    {
        $classes = ClassInfo::ancestry($this->getOwner()->getField('ClassName'));
        $classes = array_reverse($classes);
        $baseClass = BaseElement::class;
        $classTemplates = [];
        $areaName = $this->getOwner()->getAreaName();
        foreach ($classes as $key => $class)
        {
            if (!empty($areaName)) {
                $classTemplates[$class][] = $class . '_' . $areaName . $suffix;
            }
            $classTemplates[$class][] = $class . $suffix;
            if ($class === $baseClass) {
                break;
            }
        }
        $this->getOwner()->extend('updateBetterRenderTemplates', $classTemplates, $suffix);
        $templates = $classTemplates;
    }


    /**
     * BetterElemental helpers
     * ----------------------------------------------------
     */

    public function isBetterElementalConfigured(): bool
    {
        $isConfigured = $this->getOwner()->isBetterElementalConfigured;
        if (is_bool($isConfigured)) {
            return $isConfigured;
        }
        $isConfigured = $this->getOwner()->hasMethod('isUsingBetterElementalTrait')
            && $this->getOwner()->isUsingBetterElementalTrait();
        $this->getOwner()->isBetterElementalConfigured = $isConfigured;
        return $isConfigured;
    }


    /**
     * Parent Area
     * ----------------------------------------------------
     */

    public function getArea(): ?BetterElementalArea
    {
        return $this->getOwner()->getCurrentArea() ?? $this->getOwner()->getLocalArea();
    }

    public function getAreaName(): ?string
    {
        $name = $this->getOwner()->getCachedAreaName();
        if (!empty($name)) {
            return $name;
        }
        $area = $this->getOwner()->getArea();
        $name = is_null($area) ? null : $area->getName();
        $this->getOwner()->setCachedAreaName($name);
        return $name;
    }

    public function getLocalArea(bool $doUseCache = true): ?BetterElementalArea
    {
        if ($doUseCache) {
            $area = $this->getOwner()->getCachedLocalArea();
            if (!is_null($area)) {
                return $area;
            }
        }
        $area = null;
        $areaID = (int) $this->getOwner()->getField('ParentID');
        if ($areaID > 0) {
            /** @var BetterElementalArea $area */
            $area = BetterElementalArea::get()->find('ID', $areaID);
        }
        $this->getOwner()->setCachedLocalArea($area);
        return $area;
    }

    public function setCurrentArea(?BetterElementalArea $area): BaseElement
    {
        return $this->getOwner()->setCachedCurrentArea($area);
    }

    public function getCurrentArea(): ?BetterElementalArea
    {
        return $this->getOwner()->getCachedCurrentArea();
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

    public function isElementEmpty(): bool
    {
        $isEmpty = false;
        $this->getOwner()->invokeWithExtensions('updateIsElementEmpty', $isEmpty);
        return $isEmpty;
    }

    public function isElementalAreasContainer(): bool
    {
        return $this->getOwner()::has_extension(ElementalAreaController::class);
    }

    public function getTopArea(): ?BetterElementalArea
    {
        $area = $this->getOwner()->getArea();
        return is_null($area) ? null : $area->getTopArea();
    }

    public function getTopContainer(): ?DataObject
    {
        $area = $this->getOwner()->getArea();
        return is_null($area) ? null : $area->getTopContainer();
    }

    public function getTopPage(): ?SiteTree
    {
        $area = $this->getOwner()->getArea();
        return is_null($area) ? null : $area->getTopPage();
    }

    public function getHandlerURLSegment(): ?string
    {
        $segment = null;
        $area = $this->getTopArea();
        if (!is_null($area)) {
            $segment = Controller::join_links(
                'area',
                $area->getURLSegment(),
                $this->getOwner()->getField('ID')
            );
        }
        return $segment;
    }


    /**
     * Element providers
     * ----------------------------------------------------
     * Used in conjunction with BetterElementalArea, for sharing
     * elements between Areas. This element should either provide elements,
     * that is, when its Area is creating its list of Elements, instead of
     * providing this element, this will be replaced with the elements
     * supplied by provideElements().
     * Elements supplied via this mechanism will have the original element
     * (this element, in the above example, as a ProviderElement). This
     * allows an element to use isAnchorEnabled/isMenuVisibilityEnabled, etc
     * of its ProviderElement, rather than its own (elements used in a shared
     * context likely have those enable flags configured as false as they are
     * meaningless out of context of a real Area on a real Page/DO.)
     */

    public function setProviderElement(BaseElement $element): BaseElement
    {
        return $this->getOwner()->setCachedProviderElement($element);
    }

    public function getProviderElement(): ?BaseElement
    {
        return $this->getOwner()->getCachedProviderElement();
    }

    public function provideElements(): ?SS_List
    {
        $elements = null;
        $this->getOwner()->invokeWithExtensions('updateProvideElements', $elements);
        return $elements;
    }


    /**
     * Variables/helpers intended/available for use on front-end
     * ----------------------------------------------------
     */

    public function getShortClassName(bool $lowercase = false): string
    {
        $className = ClassInfo::shortName($this->getOwner());
        if ($lowercase) $className = mb_strtolower($className);
        return $className;
    }


    /**
     * Elemental Grid (based on TheWebmen module)
     * ----------------------------------------------------
     */

    public function isGridEnabled(): bool
    {
        if (!$this->getOwner()->isBetterElementalConfigured()) {
            return false;
        }
        $area = $this->getOwner()->getArea();
        $isEnabled = !is_null($area) && $area->isGridEnabled();
        $this->getOwner()->invokeWithExtensions('updateIsGridEnabled', $isEnabled);
        return $isEnabled;
    }

    public function isGridRow(): bool
    {
        $isRow = (bool) $this->getOwner()->config()->get('is_grid_row');
        $this->getOwner()->invokeWithExtensions('updateIsGridRow', $isRow);
        return $isRow;
    }

    public function getDefaultGridViewport(): string
    {
        return 'MD';
    }

    public function handleDefaultGridSettings(): void
    {
        $size = $this->getOwner()->getGridColumnsCount();
        $viewport = $this->getOwner()->getDefaultGridViewport();
        $this->getOwner()->setField('Size' . $viewport, $size);
    }

    public function getGridCSSFramework(): CSSFrameworkInterface
    {
        $framework = $this->getOwner()->gridCSSFramework;
        if (!is_null($framework)) {
            return $framework;
        }
        $framework = new BootstrapCSSFramework($this->getOwner());
        $this->getOwner()->gridCSSFramework = $framework;
        return $framework;
    }

    public function getColumnClasses(): string
    {
        return $this->getOwner()->getGridCSSFramework()->getColumnClasses();
    }

    public function updateGridBlockSchema(&$blockSchema): void
    {
    }

    public function getColumnSizeOptions($defaultValue = null): array
    {
        // Returns an array of all possibile column widths
        $columns = [];
        if ($defaultValue) {
            $columns[0] = $defaultValue;
        }
        for ($i = 1; $i < $this->getOwner()->getGridColumnsCount() + 1; $i++) {
            $columns[$i] = sprintf('%s %u/%u', _t(__CLASS__ . '.COLUMN', 'Column'), $i, $this->getOwner()->getGridColumnsCount());
        }
        return $columns;
    }

    public function getColumnVisibilityOptions(): array
    {
        return [
            'visible' => _t(__CLASS__ . '.VISIBLE', 'Visible'),
            'hidden' => _t(__CLASS__ . '.HIDDEN', 'Hidden'),
        ];
    }

    public function getGridColumnsCount(): int
    {
        return $this->getOwner()->config()->get('grid_columns_count');
    }


    /**
     * Data processing and validation methods
     * ----------------------------------------------------
     */

    public function populateDefaults()
    {
        $this->getOwner()->handleDefaultGridSettings();
    }

    public function onBeforeWrite(): void
    {
        $this->getOwner()->enforceTitleSettings();
        $this->getOwner()->enforceAnchorSettings();
        $this->getOwner()->handleEmptySortValue();
    }

    public function handleEmptySortValue(): void
    {
        $sort = (int) $this->getOwner()->getField('Sort');
        if (empty($sort)) {
            $localArea = $this->getOwner()->getLocalArea(false);
            if (!is_null($localArea)) {
                $sort = $localArea->getAllLocalElements(false)->max('Sort') + 1;
                $this->getOwner()->setField('Sort', $sort);
            }
        }
    }


    /**
     * CMS Inline/React helpers
     * ----------------------------------------------------
     */

    public function updateBlockSchema(array &$schema): void
    {
        $schema['content'] = $this->getOwner()->getInlineCMSSummary();
        $schema['fileURL'] = $this->getOwner()->getInlineCMSImageURL();
        $schema['fileTitle'] = $this->getOwner()->getInlineCMSImageTitle();

        $defaultViewport = $this->getOwner()->getDefaultGridViewport();
        $isGridEnabled = $this->getOwner()->isGridEnabled();
        $isGridRow = $this->getOwner()->isGridRow();
        $gridSize = $isGridEnabled ? $this->getOwner()->getField('Size' . $defaultViewport) : null;
        if (empty($gridSize)) $gridSize = $this->getOwner()->getGridColumnsCount();
        $gridOffset = $isGridEnabled ? $this->getOwner()->getField('Offset' . $defaultViewport) : null;
        $gridVisibility = $isGridEnabled ? $this->getOwner()->getField('Visibility' . $defaultViewport) : null;
        $schema['grid'] = [
            'isEnabled' => $isGridEnabled,
            'isRow' => !$isGridEnabled || $isGridRow,
            'gridColumns' => $this->getOwner()->getGridColumnsCount(),
            'column' => [
                'defaultViewport' => $defaultViewport,
                'size' => $gridSize,
                'offset' => $gridOffset,
                'visibility' => $gridVisibility,
            ],
        ];
    }

    public function getCMSTitle(): string
    {
        $title = $this->getOwner()->getLocalTitle();
        if (empty($title)) {
            $title = $this->getOwner()->isTitleEnabled()
                ? $this->getOwner()->getDefaultTitle()
                : $this->getOwner()->getName();
        }
        return $title;
    }

    public function isCMSFormInline(): bool
    {
        $curr = Controller::curr();
        return !is_null($curr)
            && is_a($curr, ElementalAreaController::class);
    }

    public function isInlineEditable(): bool
    {
        $isInline = (bool) $this->getOwner()->config()->get('inline_editable');
        $this->getOwner()->invokeWithExtensions('updateIsInlineEditable', $isInline);
        return $isInline;
    }

    public function getInlineCMSTitle(): string
    {
        $parts = $this->getOwner()->getInlineCMSTitleParts();
        $parts = array_values(array_filter($parts));
        return implode("\r\n", $parts);
    }

    public function getInlineCMSTitleParts(): array
    {
        $parts = [
            'type' => $this->getOwner()->getType(),
            'title' => $this->getOwner()->getCMSTitle()
        ];
        $this->getOwner()->invokeWithExtensions('updateInlineCMSTitleParts', $parts);
        return $parts;
    }

    public function getInlineCMSSummary(): ?string
    {
        $parts = $this->getOwner()->getInlineCMSSummaryParts();
        $parts = array_values(array_filter($parts));
        $summary = implode("\r\n", $parts);
        return empty($summary) ? null : $summary;
    }

    public function getInlineCMSSummaryParts(): array
    {
        return [];
    }

    public function getInlineCMSImage(): ?Image
    {
        $image = null;
        $this->getOwner()->invokeWithExtensions('updateInlineCMSImage', $image);
        return $image;
    }

    public function getInlineCMSImageURL(): ?string
    {
        $image = $this->getOwner()->getInlineCMSImage();
        return is_null($image) ? null : $image->getURL();
    }

    public function getInlineCMSImageTitle(): ?string
    {
        $image = $this->getOwner()->getInlineCMSImage();
        return is_null($image) ? null : $image->getTitle();
    }

    public function getInlineCMSFields(): FieldList
    {
        $fields = FieldList::create(
            TabSet::create('Root',
                $mainTab = Tab::create('Main', $this->getOwner()->fieldLabel('Main'))
            )
        );

        $baseInstance = BetterBaseElement::singleton();
        $scaffoldFields = $baseInstance->scaffoldFormFields([
            'tabbed' => false,
            'includeRelations' => false,
            'restrictFields' => false,
            'fieldClasses' => false,
            'ajaxSafe' => true
        ]);

        $scaffoldFields->removeByName([
            'ShowInMenus',
            'AnchorName',
            'ShowTitle',
            'Sort',
            'ExtraClass',
            'Style',
            'Version',
            'CMSName',
            'ParentID',

            'SizeXS',
            'SizeSM',
            'SizeMD',
            'SizeLG',
            'SizeXL',
            'OffsetXS',
            'OffsetSM',
            'OffsetMD',
            'OffsetLG',
            'OffsetXL',
            'VisibilityXS',
            'VisibilitySM',
            'VisibilityMD',
            'VisibilityLG',
            'VisibilityXL'
        ]);

        if ($this->getOwner()->isGridEnabled() && !$this->getOwner()->isGridRow()) {
            $scaffoldFields->push(HiddenField::create('SizeMD'));
            $scaffoldFields->push(HiddenField::create('OffsetMD'));
        }

        $htmlTextRows = (int) EditFormFactory::config()->get('html_field_rows');
        if ($htmlTextRows < 1) {
            $htmlTextRows = 7;
        }
        foreach ($scaffoldFields as $scaffoldField)
        {
            if (is_a($scaffoldField, HTMLEditorField::class, false)) {
                $scaffoldField->setRows($htmlTextRows);
            }
            $mainTab->push($scaffoldField);
        }

        if ($this->getOwner()->isTitleEnabled()) {
            $titleField = $this->getOwner()->getTitleField();
            $fields->replaceField('Title', $titleField);
        }
        else {
            $fields->removeByName('Title');
        }

        $rootTabSet = $fields->findOrMakeTab('Root');
        $rootTabSet->setSchemaState(['hideNav' => true]);

        $settingsTab = $fields->findOrMakeTab('Root.Settings');

        if (!$this->isTitleEnabled()) {
            $nameField = $this->getOwner()->getNameField();
            $settingsTab->push($nameField);
        }

        if ($this->getOwner()->isAnchorsEnabled())
        {
            $anchorField = $this->getOwner()->getAnchorNameField();
            $settingsTab->push($anchorField);
        }

        if ($this->getOwner()->isMenuVisibilityEnabled() && !$this->getOwner()->isMenuVisibilityForced())
        {
            $isMenuField = $this->getOwner()->getMenuVisibilityField();
            $settingsTab->push($isMenuField);
        }

        $this->getOwner()->invokeWithExtensions('updateInlineCMSFields', $fields);

        if ($this->getOwner()->isAdvancedEditEnabled())
        {
            $rootTabSet = $fields->findOrMakeTab('Root');
            /** @var Tab $firstTab */
            $firstTab = $rootTabSet->Tabs()->first();
            if ($firstTab)
            {
                $firstTabFields = $firstTab->Fields();
//                $advButton = $this->getOwner()->getAdvancedEditButtonField();
//                if (!is_null($advButton)) {
//                    $firstTabFields->unshift($advButton);
//                }
                $advMessage = $this->getOwner()->getAdvancedEditMessageField();
                if (!is_null($advMessage)) {
                    $firstTabFields->push($advMessage);
                }
            }
        }

        if ($settingsTab->Fields()->count() < 1) {
            $fields->remove($settingsTab);
        }

        return $fields;
    }

    public function beforeUpdateInlineCMSFields(callable $callback): void
    {
        $this->getOwner()->beforeExtending('updateInlineCMSFields', $callback);
    }

    public function afterUpdateInlineCMSFields(callable $callback): void
    {
        $this->getOwner()->afterExtending('updateInlineCMSFields', $callback);
    }


    /**
     * Advanced Edit button/message for Inline/React form
     * ----------------------------------------------------
     */

    public function isAdvancedEditEnabled(): bool
    {
        $isEnabled = $this->getOwner()->config()->get('is_advanced_edit_enabled');
        $this->getOwner()->invokeWithExtensions('updateIsAdvancedEditEnabled');
        return $isEnabled;
    }

    public function AdvancedEditLink(): ?string
    {
        return $this->getOwner()->CMSEditLink();
    }

    public function getAdvancedEditMessage(): ?string
    {
        $message = null;
        $link = $this->getOwner()->AdvancedEditLink();
        if (!empty($link)) {
            $message = 'Use the <a href="' . $link . '">advanced edit form</a>';
            $instruction = $this->getOwner()->config()->get('advanced_edit_instruction');
            if (!empty($instruction)) {
                $message .= ' ' . $instruction;
            }
        }
        $this->getOwner()->invokeWithExtensions('updateAdvancedEditMessage', $message);
        return $message;
    }

    public function getAdvancedEditButtonField(string $name = 'AdvancedEditButton'): ?FormField
    {
        $field = LiteralField::create('AdvancedEditLink',
            '<p>
                <a href="' . $this->getOwner()->AdvancedEditLink() . '" class="btn action Button btn-outline-secondary font-icon-edit-list">
                    <span class="btn__title">' . $this->getOwner()->fieldLabel($name) . '</span>
                </a>
            </p>'
        );
        $this->getOwner()->invokeWithExtensions('updateAdvancedEditButtonField', $field, $name);
        return $field;
    }

    public function getAdvancedEditMessageField(string $name = 'AdvancedEditMessage'): ?FormField
    {
        $field = null;
        $message = $this->getAdvancedEditMessage();
        if (!empty($message)) {
            $field = LiteralField::create($name,
                '<div class="form-group"><div class="form__field-holder"><p style="margin-top: 26px;">'
                . $message
                . '</p></div></div>'
            );
        }
        $this->getOwner()->invokeWithExtensions('updateAdvancedEditButtonField', $field, $name);
        return $field;
    }


    /**
     * CMS fields
     * ----------------------------------------------------
     */

    public function isCMSHistoryEnabled(): bool
    {
        return (bool) $this->getOwner()->config()->get('is_cms_history_enabled');
    }

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName([
            'ExtraClass',
            'Style',
            'TopPageID',
            'ShowInMenus',
            'AnchorName',
            'ShowTitle',
            'Sort',
            'Style',
            'Version',
            'CMSName',
            'ParentID',

            'SizeXS',
            'SizeSM',
            'SizeMD',
            'SizeLG',
            'SizeXL',
            'OffsetXS',
            'OffsetSM',
            'OffsetMD',
            'OffsetLG',
            'OffsetXL',
            'VisibilityXS',
            'VisibilitySM',
            'VisibilityMD',
            'VisibilityLG',
            'VisibilityXL'
        ]);

        if ($this->getOwner()->isGridEnabled() && !$this->getOwner()->isGridRow()) {
            $fields->push(HiddenField::create('SizeMD'));
            $fields->push(HiddenField::create('OffsetMD'));
        }

        if (!$this->getOwner()->isCMSHistoryEnabled()) {
            $fields->removeByName('History');
        }

        $fields->removeByName(['Title', 'ShowTitle']);
        if ($this->getOwner()->isTitleEnabled()) {
            $titleField = $this->getOwner()->getTitleField();
            $mainTab = $fields->findOrMakeTab('Root.Main');
            $mainTab->unshift($titleField);
        }

        $settingsTab = $fields->findOrMakeTab('Root.Settings');
        if (!$this->isTitleEnabled()) {
            $nameField = $this->getOwner()->getNameField();
            $settingsTab->push($nameField);
        }
        if ($this->getOwner()->isAnchorsEnabled())
        {
            $anchorField = $this->getOwner()->getAnchorNameField();
            $settingsTab->push($anchorField);
        }
        if ($this->getOwner()->isMenuVisibilityEnabled() && !$this->getOwner()->isMenuVisibilityForced())
        {
            $isMenuField = $this->getOwner()->getMenuVisibilityField();
            $settingsTab->push($isMenuField);
        }
    }

    public function insertContentTabSet(FieldList $fields): FieldList
    {
        if ($fields->fieldByName('ContentTabSet')) {
            return $fields;
        }

        $rootTabSet = $fields->fieldByName('Root');
        if (!$rootTabSet) {
            return $fields;
        }

        $rootTabs = $rootTabSet->Tabs();
        $contentTabSet = TabSet::create('ContentTabSet', 'Content');
        $skipTabs = ['Settings', 'History'];
        foreach ($rootTabs as $rootTab)
        {
            if (!in_array($rootTab->getName(), $skipTabs)) {
                $fields->removeByName($rootTab->getName());
                if ($rootTab->getName() === 'Main') {
                    $rootTab->setTitle('Main');
                }
                $contentTabSet->push($rootTab);
            }
        }
        $rootTabSet->unshift($contentTabSet);
        return $fields;
    }

    public function insertSettingsTabSet(FieldList $fields): FieldList
    {
        if ($fields->fieldByName('SettingsTabSet')) {
            return $fields;
        }

        $rootTabSet = $fields->fieldByName('Root');
        if (!$rootTabSet) {
            return $fields;
        }

        $oldSettingsTab = $fields->fieldByName('Root.Settings');
        if ($oldSettingsTab)
        {
            $fields->removeByName($oldSettingsTab->getName());
            $settingsFields = $oldSettingsTab->Fields();
            if ($settingsFields && $settingsFields->count() > 0) {
                $settingsTabSet = $this->getOwner()->convertFieldListToTabSet(
                    'Settings',
                    $settingsFields
                );
                $rootTabSet->push($settingsTabSet);
            }
        }
        return $fields;
    }

    public function convertFieldListToTabSet($title, FieldList $fields): TabSet
    {
        $tabSet = TabSet::create($title . 'TabSet', $title);
        if ($fields->count() > 0) {
            $tab = Tab::create($title . 'MainTab', 'Main');
            $tabSet->push($tab);
            foreach ($fields as $field) {
                $tab->push($field);
            }
        }
        return $tabSet;
    }


    /**
     * Permissions
     * ----------------------------------------------------
     */

    public function isAdminCurrController(): bool
    {
        $curr = Controller::curr();
        return is_a($curr, LeftAndMain::class, false)
            || is_a($curr, GraphQLController::class, false);
    }

    public function isBaseElement(): bool
    {
        return get_class($this->getOwner()) === BaseElement::class;
    }

    public function canView($member = null): ?bool
    {
        if (!$this->getOwner()->isAdminCurrController()) {
            return !$this->getOwner()->isElementEmpty();
        }
        $hasCMSAccess = Permission::check('CMS_ACCESS', 'any', $member);
        if ($this->getOwner()->isBaseElement()) {
            return $hasCMSAccess ? true : null;
        }
        $area = $this->getOwner()->getArea();
        if (!is_null($area)) {
            return $area->canView($member);
        }
        return $hasCMSAccess ? true : null;
    }

    public function canEdit($member = null): ?bool
    {
        $hasCMSAccess = Permission::check('CMS_ACCESS', 'any', $member);
        if ($this->getOwner()->isBaseElement()) {
            return $hasCMSAccess ? true : null;
        }
        $area = $this->getOwner()->getArea();
        if (!is_null($area)) {
            return $area->canEdit($member);
        }
        return $hasCMSAccess ? true : null;
    }

    public function canCreate($member = null, $context = []): ?bool
    {
        $hasCMSAccess = Permission::check('CMS_ACCESS', 'any', $member);
        if ($this->getOwner()->isBaseElement()) {
            return $hasCMSAccess ? true : null;
        }
        $area = $this->getOwner()->getArea();
        if (!is_null($area)) {
            return $area->canCreate($member, $context);
        }
        return $hasCMSAccess ? true : null;
    }

    public function canDelete($member = null): ?bool
    {
        $hasCMSAccess = Permission::check('CMS_ACCESS', 'any', $member);
        if ($this->getOwner()->isBaseElement()) {
            return $hasCMSAccess ? true : null;
        }
        $area = $this->getOwner()->getArea();
        if (!is_null($area)) {
            return $area->canDelete($member);
        }
        return $hasCMSAccess ? true : null;
    }


    /**
     * @return BaseElement&BaseElementExtension
     */
    public function getOwner(): BaseElement
    {
        /** @var BaseElement&BaseElementExtension $owner */
        $owner = parent::getOwner();
        return $owner;
    }
}
