<?php

namespace Fromholdio\Elemental\Base\CSSFramework;

interface CSSFrameworkInterface
{
    /**
     * @return string
     */
    public function getColumnClasses(): string;

    /***
     * @param bool $fluid
     * @return mixed
     */
    public function getContainerClass(bool $fluid): string;
}
