<?php

namespace Fromholdio\Elemental\Base\Controllers;

use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\SSViewer;

class BetterElementController extends ElementController
{
    /**
     * Templates/Rendering
     * ----------------------------------------------------
     */

    public function forTemplate(): ?DBHTMLText
    {
        $templates = $this->getElement()->getHolderTemplates();
        return empty($templates)
            ? null
            : $this->renderWith(SSViewer::create($templates));
    }


    /**
     * Links
     * ----------------------------------------------------
     */

    public function HandlerLink(?string $action = null): ?string
    {
        $link = null;
        $element = $this->getElement();
        $segment = $element->getHandlerURLSegment();
        if (!is_null($segment)) {
            $curr = Controller::curr();
            if (!is_null($curr) && $curr->hasMethod('Link')) {
                $link = Controller::join_links(
                    $curr->Link(),
                    $segment
                );
            }
        }
        return $link;
    }

    public function Link($action = null): ?string
    {
        $link = null;
        $curr = Controller::curr();
        if (!is_null($curr) && !is_a($curr, self::class, false)) {
            $link = Controller::join_links(
                $curr->Link($action),
                '#' . $this->getElement()->getAnchor()
            );
        }
        $this->extend('updateLink', $link, $action);
        return $link;
    }

    public function AbsoluteLink($action = null): ?string
    {
        $link = null;
        $curr = Controller::curr();
        if (!is_null($curr) && !is_a($curr, self::class, false)) {
            $link = Controller::join_links(
                $curr->AbsoluteLink($action),
                '#' . $this->getElement()->getAnchor()
            );
        }
        $this->extend('updateAbsoluteLink', $link, $action);
        return $link;
    }
}
