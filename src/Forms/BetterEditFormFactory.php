<?php

namespace Fromholdio\Elemental\Base\Forms;

use DNADesign\Elemental\Forms\EditFormFactory;
use SilverStripe\Control\RequestHandler;

class BetterEditFormFactory extends EditFormFactory
{
    protected function getFormFields(RequestHandler $controller = null, $name, $context = [])
    {
        $fields = $context['Record']->getInlineCMSFields();
        $this->invokeWithExtensions('updateFormFields', $fields, $controller, $name, $context);
        return $fields;
    }
}
