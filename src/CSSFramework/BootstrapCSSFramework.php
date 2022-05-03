<?php

namespace Fromholdio\Elemental\Base\CSSFramework;

use DNADesign\Elemental\Models\BaseElement;

class BootstrapCSSFramework implements CSSFrameworkInterface
{
    protected BaseElement $baseElement;

    public const COLUMN_CLASSNAME = 'col';

    public const ROW_CLASSNAME = 'row';

    public const CONTAINER_CLASSNAME = 'container';

    public const FLUID_CONTAINER_CLASSNAME = 'container-fluid';

    public function __construct(BaseElement $baseElement)
    {
        $this->baseElement = $baseElement;
    }

    public function getRowClasses(): string
    {
        return self::ROW_CLASSNAME;
    }

    public function getColumnClasses(): string
    {
        $sizeClasses = $this->getSizeClasses();
        $offsetClasses = $this->getOffsetClasses();
        $visibilityClasses = $this->getVisibilityClasses();
        $classes = array_merge($sizeClasses, $offsetClasses, $visibilityClasses);
        return implode(' ', $classes);
    }

    public function getVisibilityClasses(): array
    {
        $classes = [];
        if ($this->baseElement->VisibilityXS === 'hidden') {
            array_push($classes, 'd-none d-sm-block');
        }
        if ($this->baseElement->VisibilitySM === 'hidden') {
            array_push($classes, 'd-sm-none d-md-block');
        }
        if ($this->baseElement->VisibilityMD === 'hidden') {
            array_push($classes, 'd-md-none d-lg-block');
        }
        if ($this->baseElement->VisibilityLG === 'hidden') {
            array_push($classes, 'd-lg-none d-xl-block');
        }
        if ($this->baseElement->VisibilityXL === 'hidden') {
            array_push($classes, 'd-xl-none');
        }
        return $classes;
    }

    public function getSizeClasses(): array
    {
        $classes = [];
        if ($this->baseElement->SizeXS) {
            array_push($classes, 'xs-' . $this->baseElement->SizeXS);
        }
        if ($this->baseElement->SizeSM) {
            array_push($classes, 'sm-' . $this->baseElement->SizeSM);
        }
        if ($this->baseElement->SizeMD) {
            array_push($classes, 'md-' . $this->baseElement->SizeMD);
        }
        if ($this->baseElement->SizeLG) {
            array_push($classes, 'lg-' . $this->baseElement->SizeLG);
        }
        if ($this->baseElement->SizeXL) {
            array_push($classes, 'xl-' . $this->baseElement->SizeXL);
        }
        foreach ($classes as &$class) {
            $class = sprintf('%s-%s', self::COLUMN_CLASSNAME, $class);
        }
        return $classes;
    }

    public function getOffsetClasses(): array
    {
        $classes = [];
        if ($this->baseElement->OffsetXS) {
            array_push($classes, 'offset-xs-' . $this->baseElement->OffsetXS);
        }
        if ($this->baseElement->OffsetSM) {
            array_push($classes, 'offset-sm-' . $this->baseElement->OffsetSM);
        }
        if ($this->baseElement->OffsetMD) {
            array_push($classes, 'offset-md-' . $this->baseElement->OffsetMD);
        }
        if ($this->baseElement->OffsetLG) {
            array_push($classes, 'offset-lg-' . $this->baseElement->OffsetLG);
        }
        if ($this->baseElement->OffsetXL) {
            array_push($classes, 'offset-xl-' . $this->baseElement->OffsetXL);
        }
        return $classes;
    }

    public function getContainerClass(bool $fluid): string
    {
        if ($fluid) {
            return self::FLUID_CONTAINER_CLASSNAME;
        }
        return self::CONTAINER_CLASSNAME;
    }
}
