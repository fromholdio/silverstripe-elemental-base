# Installation

Install the module with Composer:

```bash
composer require fromholdio/silverstripe-elemental-base
vendor/bin/sake dev/build flush=1
```

The module is a Silverstripe vendor module. Its default config loads after upstream Elemental and exposes prebuilt CMS assets from `client/dist`.

## Requirements

The package requires:

- `dnadesign/silverstripe-elemental`
- `fromholdio/silverstripe-checkboxfieldgroup`
- `fromholdio/silverstripe-cms-fields-placement`
- `fromholdio/silverstripe-empty-extension`
- `lekoala/silverstripe-cms-actions`

The upstream Elemental 6 line requires Silverstripe CMS 6 and PHP 8.3 or newer.

## What The Module Configures

The default config:

- gives this module higher module priority than upstream Elemental
- loads this module's CMS JavaScript and CSS
- replaces upstream Elemental services with `Evo` variants
- disables the stock `element/$ID` content-controller handler
- adds `ElementsRouter` to `ContentController`
- applies `ElementalAreasContainer` to `Page` and `SiteConfig`
- applies `BaseElementExtension` to `DNADesign\Elemental\Models\BaseElement`

The module does not create a usable page area by itself. You still define the areas your project needs.

## A Minimal Page Area

```php
namespace App\PageTypes;

use Fromholdio\Elemental\Base\Model\EvoElementalArea;
use Page;

class ContentPage extends Page
{
    private static $table_name = 'ContentPage';

    private static $has_one = [
        'ContentArea' => EvoElementalArea::class,
    ];

    private static $cascade_deletes = [
        'ContentArea',
    ];

    private static $cascade_duplicates = [
        'ContentArea',
    ];

    private static $elemental_areas = [
        'ContentArea' => [
            'enabled' => true,
            'url_segment' => 'content',
            'cms_fields' => [
                'tab_path' => 'Root.Main',
            ],
        ],
    ];
}
```

Then render it in a template:

```ss
<% if $ContentArea %>
    $ContentArea
<% end_if %>
```

`ElementalAreasContainer` is applied to `Page` by default, so page subclasses inherit the extension. For other `DataObject` classes, add the extension yourself:

```php
use Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer;

private static $extensions = [
    ElementalAreasContainer::class,
];
```

## Custom Area Classes

For most real projects, create dedicated area classes. This gives you separate tables, per-area defaults, and a clean place for area-specific behavior.

```php
namespace App\Elemental\Areas;

use Fromholdio\Elemental\Base\Model\EvoElementalArea;

class SidebarElementalArea extends EvoElementalArea
{
    private static $table_name = 'SidebarElementalArea';

    private static $is_anchors_enabled = false;

    private static $element_classes = [
        'disallowed' => [
            App\Elemental\Elements\ElementHero::class,
        ],
    ];
}
```

## After Installation

Run a dev build after adding or changing area relations:

```bash
vendor/bin/sake dev/build flush=1
```

If you add or change CMS assets in this module itself, run the module frontend build from the package directory:

```bash
yarn install
yarn build
```

Most projects using the package from Composer do not need to rebuild the module assets.
