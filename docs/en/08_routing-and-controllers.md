# Routing And Controllers

Element routing is one of the places where this module deliberately diverges from stock Elemental.

Upstream Elemental uses a generic `element/$ID` route on `ContentController` and searches the page's elemental relations for the element. Current upstream Elemental still follows that shape.

This module disables that route and replaces it with an area-aware route:

```text
/area/$AreaURLSegment/$ElementID
```

The route is registered by `Fromholdio\Elemental\Base\Extensions\ElementsRouter`.

## Why The Area Segment Exists

An element ID alone does not say enough in a system with:

- multiple named areas on one page
- inherited current areas
- nested areas
- provider/shared elements
- elements that appear in different contexts

The area segment lets the router resolve the element from the intended area.

## Configuring Area Segments

Set `url_segment` on each area that needs routed element actions.

```php
private static $elemental_areas = [
    'ContentArea' => [
        'enabled' => true,
        'url_segment' => 'content',
    ],
    'SideArea' => [
        'enabled' => true,
        'url_segment' => 'side',
    ],
];
```

An element in `ContentArea` can then produce handler URLs under:

```text
/my-page/area/content/<element-id>
```

## HandlerLink

Use `HandlerLink()` from an element controller template.

```ss
<form action="$HandlerLink('submit')" method="post">
    <button type="submit">Submit</button>
</form>
```

`HandlerLink('submit')` joins:

1. the top page link
2. the element handler URL segment
3. the action

For an element in `ContentArea`, the result may be:

```text
/my-page/area/content/123/submit
```

## Custom Element Controllers

Custom element controllers must extend `EvoElementController`.

```php
namespace App\Elemental\Controllers;

use Fromholdio\Elemental\Base\Controllers\EvoElementController;

class ElementSignupController extends EvoElementController
{
    private static $url_handlers = [
        'submit' => 'submit',
    ];

    private static $allowed_actions = [
        'submit',
    ];

    public function submit()
    {
        // Handle submission.
    }
}
```

Configure the element:

```php
private static $controller_class = App\Elemental\Controllers\ElementSignupController::class;
```

If the controller does not extend `EvoElementController`, the module throws a logic exception.

`EvoElementController` is also extended with `ElementsRouter`. That allows handler lookup to continue through nested area containers when an element controller is itself the current controller context.

## Requests During Template Rendering

`EvoElementTrait::getController()` copies the current controller request onto the element controller before calling `doInit()`.

That means element controllers can access query parameters and request state when they are rendered inside a page template, not only when they are reached through a direct handler URL.

## Redirects From Element Controllers

Element controllers are often rendered inside page templates, where returning a normal `HTTPResponse` is not enough to stop page rendering.

`EvoElementController::forTemplate()` checks whether the response is finished. If it is, the response is output and execution ends.

This allows redirect logic in an element controller action or `init()` to take effect.

## Restricting Handled Areas

Set `handled_elemental_area_names` on the page controller to restrict which areas the router traverses.

```yaml
PageController:
  handled_elemental_area_names:
    - ContentArea
    - SideArea
```

This is useful when only some areas contain elements with routed actions. A decorative area or static footer area may not need handler lookup.

If the config is `null`, the router may traverse all named areas.

## The `all` Segment

The router treats the special `all` area segment as "search supported areas".

```text
/my-page/area/all/123
```

Prefer explicit area segments for new code. They are easier to reason about and avoid unnecessary traversal.

## Nested Elements

For nested areas, the route still starts from the top page and top area context. The lookup descends into child containers as needed.

This is why elements should use `HandlerLink()` rather than hand-building URLs.

## Link Helpers

Element and controller links resolve through the top container:

```php
$element->Link();
$element->AbsoluteLink();
$controller->Link();
$controller->AbsoluteLink();
```

These return links with the element anchor when a top container with a `Link()` or `AbsoluteLink()` method is available.

For handler actions, use:

```php
$controller->HandlerLink('action');
```

## Migration Notes From Stock Routes

If a project already uses upstream Elemental handler URLs like:

```text
/my-page/element/123/action
```

update templates and controller code to use `HandlerLink()` so URLs include the area segment.

Avoid storing handler URLs long-term. Generate them when rendering.
