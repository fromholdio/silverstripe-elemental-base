# Permissions

Vendor elemental gates blocks largely through their parent. elemental-base adds
**per-element-class** permission codes on top of that, so you can grant or withhold
the ability to manage a particular *type* of block, and it makes element permissions
cascade through the area to the container.

- [Permission codes](#permission-codes)
- [How a check resolves](#how-a-check-resolves)
- [Cascade to the container](#cascade-to-the-container)
- [Front-end visibility](#front-end-visibility)
- [Granting the codes](#granting-the-codes)

## Permission codes

Each element class derives four action codes plus a manage code from its short class
name:

```php
$element->getCanPermissionCode('view');    // VIEW_ELEMENT_CONTENTELEMENT
$element->getCanPermissionCode('edit');    // EDIT_ELEMENT_CONTENTELEMENT
$element->getCanPermissionCode('create');  // CREATE_ELEMENT_CONTENTELEMENT
$element->getCanPermissionCode('delete');  // DELETE_ELEMENT_CONTENTELEMENT
$element->getCanPermissionCode('manage');  // MANAGE_ELEMENT_CONTENTELEMENT
```

The pattern is `{ACTION}_ELEMENT_{SHORTCLASSNAME}`. There is also a single global
override code, `MANAGE_ELEMENTS_ALL`.

## How a check resolves

For a given action on an element, `checkPermissionCode($action)` grants access if the
member holds **any** of:

1. the specific action code — e.g. `EDIT_ELEMENT_CONTENTELEMENT`;
2. the class manage code — `MANAGE_ELEMENT_CONTENTELEMENT`; or
3. the global `MANAGE_ELEMENTS_ALL`.

So `MANAGE_ELEMENT_CONTENTELEMENT` lets a group do everything with content blocks, and
`MANAGE_ELEMENTS_ALL` lets them manage every block type — while finer codes let you,
say, allow editing but not creating a particular block.

These checks apply in CMS/admin contexts. As always in SilverStripe, members with full
administrative access pass all checks.

## Cascade to the container

An element's `canView/canEdit/canCreate/canDelete` combine the per-class code with the
**area's** permission for the same action — and the area delegates to its
**container**:

```
element.canEdit  =  checkPermissionCode('edit')  AND  area.canEdit
area.canEdit     =  localContainer.canEdit        (or CMS access if no container)
```

The practical effects:

- A member can only edit a block if they can also edit the page (or other container)
  it belongs to — element permissions *narrow*, they don't widen, container access.
- Element `canEdit`/`canDelete` resolve against the element's **local** container —
  the place it is stored. So an [inherited or shared block](05_inheritance-and-sharing.md)
  is editable only where it lives (e.g. on `SiteConfig`), not on every page that
  displays it.

## Front-end visibility

On the front end (outside the CMS), an element's `canView()` does not consult
permission codes at all — it returns whether the element is non-empty
(`!isElementEmpty()`). That is what lets an element opt out of rendering when it has no
content, and it keeps the permission codes strictly a CMS concern.

## Granting the codes

The codes are ordinary SilverStripe permission codes, checked with
`Permission::checkMember()`. Assign them to groups or roles to grant granular access;
administrators bypass them. If you want the codes to appear as labelled checkboxes in
**Security → Groups**, expose them from a `PermissionProvider` in your project — for
example returning `MANAGE_ELEMENTS_ALL` and a `MANAGE_ELEMENT_*` code per block type.

```php
use SilverStripe\Security\PermissionProvider;

class ElementPermissions implements PermissionProvider
{
    public function providePermissions(): array
    {
        return [
            'MANAGE_ELEMENTS_ALL' => [
                'name' => 'Manage all content blocks',
                'category' => 'Content blocks',
            ],
            'EDIT_ELEMENT_CONTENTELEMENT' => [
                'name' => 'Edit Content blocks',
                'category' => 'Content blocks',
            ],
        ];
    }
}
```

## Next

- [Inheritance & sharing](05_inheritance-and-sharing.md) — why shared blocks edit
  where they are stored.
- [Publishing & versioning](11_publishing-and-versioning.md) — publishing what you can
  edit.
