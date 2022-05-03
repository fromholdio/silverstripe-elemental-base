<?php

namespace Fromholdio\Elemental\Base\CSSFramework;

interface CSSFrameworkInterface
{
    /**
     * @return string
     */
    public function getColumnClasses();

    /***
     * @param bool $fluid
     * @return mixed
     */
    public function getContainerClass($fluid);
}
