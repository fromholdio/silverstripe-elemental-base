<?php

namespace Fromholdio\Elemental\Base\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class GridFieldDetailFormItemRequestExtension extends Extension
{
    public function updateBreadcrumbs($crumbs)
    {
        $record = $this->getOwner()->getRecord();
        if ($record instanceof BaseElement)
        {
            $last = $crumbs->Last();
            $last->Title = DBField::create_field('HTMLVarchar', sprintf(
                "%s <small>(%s)</small>",
                DBField::create_field('Varchar', $record->getCMSTitle())->XML(),
                $record->getType()
            ));
        }
    }

    public function updateFormActions(FieldList $actions)
    {
        /** @var DataObject $record */
        $record = $this->getOwner()->getRecord();
        $ownerHasAreas = $record
            && $record->hasMethod('getElementalAreas')
            && $record->hasMethod('doPublishWithAreas')
            && $record->config()->get('do_add_publish_with_blocks_action') !== false
            && $record->getElementalAreas();

        // Add extra actions prior to extensions so that these can be modified too
        if ($ownerHasAreas) {
            $action = CustomAction::create('doPublishWithAreas', 'Publish (including all blocks)')
                ->setShouldRefresh(true)
                ->addExtraClass('btn-outline-primary')
                ->removeExtraClass('btn-info');

            $majorActions = $actions->fieldByName('MajorActions');

            if ($majorActions) {
                $majorActions->push($action);
            } else {
                $actions->push($action);
            }
        }
    }

    public function doPublishWithAreas($data, $form)
    {
        /** @var DataObject $record */
        $record = $this->getOwner()->getRecord();

        if (
            !$record
            || !$record->hasMethod('doPublishWithAreas')
            || $record->config()->get('do_add_publish_with_blocks_action') === false
        ) {
            return $this->getOwner()->httpError(403);
        }

        $record->doPublishWithAreas();

        // Redirect back to edit
        return $this->getOwner()->redirectAfterSave(false);
    }
}
