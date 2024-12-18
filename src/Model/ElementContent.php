<?php

namespace Fromholdio\Elemental\Base\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ElementContent extends EvoBaseElement
{
    private static $table_name = 'EvoElementContent';
    private static $singular_name = 'Content';
    private static $plural_name = 'Content';
    private static $description = 'Simple content element';
    private static $icon = 'font-icon-block-content';

    private static $is_title_enabled = false;
    private static $is_title_required = false;

    private static $is_advanced_edit_enabled = true;
    private static $advanced_edit_instruction = 'to edit more settings.';

    private static $is_menu_visibility_enabled = true;
    private static $is_menu_visibility_forced = false;

    private static $is_anchors_enabled = true;
    private static $anchor_field_names = [
        'Content'
    ];

    private static $inline_editable = true;
    private static $displays_title_in_template = false;
    private static $disable_pretty_anchor_name = false;

    private static $db = [
        'Content' => 'HTMLText'
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function($fields) {
            $fields->addFieldToTab('Root.Main',
                HTMLEditorField::create('Content', $this->fieldLabel('Content'))
            );
        });
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function getInlineCMSFields(): FieldList
    {
        $this->beforeUpdateInlineCMSFields(function($fields) {
            $fields->addFieldToTab('Root.Main',
                HTMLEditorField::create('Content', $this->fieldLabel('Content'))
                    ->setRows(10)
            );
        });
        $fields = parent::getInlineCMSFields();
        return $fields;
    }
}
