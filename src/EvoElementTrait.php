<?php

namespace Fromholdio\Elemental\Base;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use Fromholdio\Elemental\Base\Controllers\EvoElementController;
use Fromholdio\Elemental\Base\Extensions\BaseElementExtension;
use Fromholdio\Elemental\Base\Model\EvoElementalArea;

/**
 * @mixin BaseElement
 * @mixin BaseElementExtension
 */
trait EvoElementTrait
{
    protected ?string $areaName = null;
    protected ?EvoElementalArea $localArea = null;
    protected ?EvoElementalArea $currentArea = null;
    protected ?BaseElement $providerElement = null;
    protected ?string $cmsEditLink = null;
    protected array $extraData = [];


    /**
     * Links - overrides of BaseElement
     * ----------------------------------------------------
     */

    public function Link($action = null): ?string
    {
        $link = null;
        $container = $this->getTopContainer();
        if (!is_null($container) && $container->hasMethod('Link'))
        {
            $link = Controller::join_links(
                $container->Link(),
                $action,
                '#' . $this->getAnchor()
            );
        }
        $this->extend('updateLink', $link);
        return $link;
    }

    public function AbsoluteLink($action = null)
    {
        $link = null;
        $container = $this->getTopContainer();
        if (!is_null($container) && $container->hasMethod('AbsoluteLink'))
        {
            $link = Controller::join_links(
                $container->AbsoluteLink(),
                $action,
                '#' . $this->getAnchor()
            );
        }
        $this->extend('updateLink', $link);
        return $link;
    }

    public function CMSEditLink($directLink = false): ?string
    {
        $link = $this->getCachedCMSEditLink();
        if (!is_null($link)) {
            return $link;
        }

        $area = $this->getLocalArea(false);
        if (is_null($area)) return null;

        $relationName = $area->getRelationName();
        if (is_null($relationName)) return null;

        $container = $area->getLocalContainer(false);
        if (is_null($container)) return null;

        if (!$container->hasMethod('CMSEditLink')) {
            return null;
        }

        $cmsEditLink = $container->CMSEditLink();
        $id = $this->getField('ID');

        if (is_a($container, SiteConfig::class, false))
        {
            $link = Controller::join_links(
                $cmsEditLink,
                'EditForm/field/' . $relationName . '/item/',
                $id
            );
        }
        elseif (is_a($container, SiteTree::class, false))
        {
            $link = Controller::join_links(
                CMSPageEditController::singleton()->Link('EditForm'),
                $container->getField('ID'),
                'field/' . $relationName . '/item/',
                $id,
                'edit'
            );

            /**
             * Inline-editable blocks link just to Page edit form
             * - I don't like this, so skipping it for now.
             */
            //if ($this->isInlineEditable() && !$directLink) {
            //  $link = $container->CMSEditLink();
            //}
        }
        else {
            $link = Controller::join_links(
                $cmsEditLink,
                'ItemEditForm/field/' . $relationName . '/item/',
                $id
            );
            $link = preg_replace('/\/item\/([\d]+)\/edit/', '/item/$1', $link);
        }

        $this->extend('updateEvoCMSEditLink', $link);
        $this->setCachedCMSEditLink($link);
        return $link;
    }


    /**
     * Variables/helpers intended/available for use on front-end
     * ----------------------------------------------------
     */

    public function First(): bool
    {
        return $this->getExtraData()['First'] ?? false;
    }

    public function Last(): bool
    {
        return $this->getExtraData()['Last'] ?? false;
    }

    public function TotalItems(): int
    {
        return $this->getExtraData()['TotalItems'] ?? 0;
    }

    public function Pos(): int
    {
        return $this->getExtraData()['Pos'] ?? 0;
    }

    public function EvenOdd(): ?string
    {
        return $this->getExtraData()['EvenOdd'] ?? null;
    }


    /**
     * Extra Data helper
     * ----------------------------------------------------
     * Provides ability to set values onto the Element, saved
     * into local property, in the context of its use.
     * eg. Allows setting position meta information like
     * First(), Last() and so forth, from the list of elements
     * in the Area to which it belongs. The Element itself shouldn't
     * have any idea of these nor be able to calculate them itself.
     * @see EvoElementalArea::getElements().
     */

    public function setExtraData(array $data): self
    {
        $this->extraData = $data;
        return $this;
    }

    public function addExtraData(array $data): self
    {
        $extraData = $this->getExtraData();
        $this->setExtraData(array_merge($extraData, $data));
        return $this;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }


    /**
     * Cache accessors with typing, for use by extensions
     * ----------------------------------------------------
     */

    public function setCachedAreaName(?string $name): self
    {
        $this->areaName = $name;
        return $this;
    }

    public function getCachedAreaName(): ?string
    {
        return $this->areaName;
    }

    public function setCachedLocalArea(?EvoElementalArea $area): self
    {
        $this->localArea = $area;
        return $this;
    }

    public function getCachedLocalArea(): ?EvoElementalArea
    {
        return $this->localArea;
    }

    public function setCachedCurrentArea(?EvoElementalArea $area): self
    {
        $this->currentArea = $area;
        return $this;
    }

    public function getCachedCurrentArea(): ?EvoElementalArea
    {
        return $this->currentArea;
    }

    public function setCachedProviderElement(?BaseElement $element): self
    {
        $this->providerElement = $element;
        return $this;
    }

    public function getCachedProviderElement(): ?BaseElement
    {
        return $this->providerElement;
    }

    public function setCachedCMSEditLink(?string $link): self
    {
        $this->cmsEditLink = $link;
        return $this;
    }

    public function getCachedCMSEditLink(): ?string
    {
        return $this->cmsEditLink;
    }

    public function isUsingEvoElementalTrait(): bool
    {
        return true;
    }


    /**
     * Fixes/Overrides
     * ----------------------------------------------------
     */

    public function getType(): string
    {
        return _t(
            __CLASS__ . '.BlockType',
            $this->i18n_singular_name()
        );
    }

    public function getController(): EvoElementController
    {
        $controller = parent::getController();
        if (!is_a($controller, EvoElementController::class, false)) {
            throw new \LogicException(
                'BaseElement objects require a Controller that is, '
                . 'or is a subclass of, EvoElementController.'
            );
        }
        return $controller;
    }

    public function getPage(): ?SiteTree
    {
        return $this->getTopPage();
    }

    public function getPageTitle(): ?string
    {
        $page = $this->getPage();
        return is_null($page) ? null : $page->getTitle();
    }

    public function getAreaRelationName(): ?string
    {
        return $this->getAreaName();
    }

    public function inlineEditable(): bool
    {
        return $this->isInlineEditable();
    }

    public function beforeUpdateInlineCMSFields(callable $callback): void
    {
        $this->beforeExtending('updateInlineCMSFields', $callback);
    }

    public function afterUpdateInlineCMSFields(callable $callback): void
    {
        $this->afterExtending('updateInlineCMSFields', $callback);
    }

    /**
     * No longer necessary/relevant
     * ----------------------------------------------------
     */

    public function getStyleVariant(): string
    {
        return '';
    }

    public function getAuthor(): ?Member
    {
        return null;
    }

    public function setAreaRelationNameCache($name): string
    {
        return $name;
    }
}
