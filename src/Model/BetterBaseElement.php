<?php

namespace Fromholdio\Elemental\Base\Model;

use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\Elemental\Base\BetterElementTrait;

class BetterBaseElement extends BaseElement
{
    use BetterElementTrait;

    private static $table_name = 'BetterBaseElement';
    private static $singular_name = 'Element';
    private static $plural_name = 'Elements';
    private static $description = 'Base element';


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields = $this->insertContentTabSet($fields);
        $fields = $this->insertSettingsTabSet($fields);
        return $fields;
    }
}
