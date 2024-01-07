<?php

namespace Fromholdio\Elemental\Base\Model;

use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\Elemental\Base\EvoElementTrait;

class EvoBaseElement extends BaseElement
{
    use EvoElementTrait;

    private static $table_name = 'EvoBaseElement';
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
