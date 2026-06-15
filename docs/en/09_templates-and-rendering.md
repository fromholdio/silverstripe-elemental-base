# Templates And Rendering

The module expands Elemental's template selection so elements can render differently by area type and area name.

## Render Chain

Area rendering follows the same broad shape as upstream Elemental, with extra context added before each element controller renders:

1. an area renders with its area template
2. the area template loops `$ElementControllers`
3. each `EvoElementController` renders a holder template
4. the holder template outputs `$ElementForTemplate`
5. the element renders with its element template stack

The module does not try to own your front-end design system. Projects should provide the area, holder, and element templates that match their markup.

## Minimum Templates

A simple area template can loop the element controllers:

```ss
<% if $ElementControllers %>
    <% loop $ElementControllers %>
        $Me
    <% end_loop %>
<% end_if %>
```

A simple holder template can wrap the rendered element:

```ss
<section id="$Element.Anchor" class="element element--$Element.SimpleClassName">
    $ElementForTemplate
</section>
```

Then each project element should provide its own element template, for example:

```ss
<%-- templates/App/Elemental/Elements/ElementCallout.ss --%>
<% if $Title %>
    <h2>$Title</h2>
<% end_if %>
$Content
```

Use these as starting points only. Most projects will want area-specific wrappers, design-system classes, and element-specific markup.

## Area Templates

`EvoElementalArea::getRenderTemplates()` builds template candidates from the area class and area name.

For an area class:

```php
App\Elemental\Areas\ComponentsElementalArea
```

with area name:

```text
ContentArea
```

the template candidates include:

```text
App/Elemental/Areas/ComponentsElementalArea_ContentArea.ss
App/Elemental/Areas/ComponentsElementalArea.ss
Fromholdio/Elemental/Base/Model/EvoElementalArea_ContentArea.ss
Fromholdio/Elemental/Base/Model/EvoElementalArea.ss
```

Silverstripe's normal template lookup rules still apply.

## Element Templates

`BaseElementExtension::updateRenderTemplates()` adds area-aware template variants.

For an element:

```php
App\Elemental\Elements\ElementFeature
```

rendered in area name:

```text
ContentArea
```

and area type:

```text
Components
```

template candidates can include:

```text
App/Elemental/Elements/ElementFeature_Components_ContentArea.ss
App/Elemental/Elements/ElementFeature_ContentArea.ss
App/Elemental/Elements/ElementFeature_Components.ss
App/Elemental/Elements/ElementFeature.ss
```

Then the stack falls back through parent element classes down to upstream `BaseElement`.

## Area Types

Area types come from the area class ancestry. If a class name ends with `ElementalArea`, that suffix is removed.

For example:

```php
App\Elemental\Areas\ComponentsElementalArea
```

adds an area type of:

```text
Components
```

This lets an element use a broad area variant such as `ElementFeature_Components.ss`, while still supporting a narrower name variant such as `ElementFeature_ContentArea.ss`.

## Holder Templates

Elements render through holder templates. By default, holder templates use the normal element template stack with `_holder` appended.

```text
App/Elemental/Elements/ElementFeature_Components_ContentArea_holder.ss
App/Elemental/Elements/ElementFeature_ContentArea_holder.ss
App/Elemental/Elements/ElementFeature_Components_holder.ss
App/Elemental/Elements/ElementFeature_holder.ss
```

Override holder templates with config:

```php
private static $holder_templates = [
    'App/Elemental/Elements/ElementFeatureCustomHolder',
];
```

Use this when the wrapper markup is fundamentally different from the normal area-aware stack.

## ElementForTemplate

`EvoElementController::ElementForTemplate()` customises the element before rendering. Extensions can add data:

```php
public function updateCustomDataForTemplate(array &$data): void
{
    $data['Variant'] = 'featured';
}
```

In the template:

```ss
<article class="feature feature--$Variant">
    $ElementForTemplate.Title
</article>
```

Most elements will not need this. Prefer normal element methods first.

## Positional Helpers

`EvoElementalArea::getElements()` adds positional data to elements before rendering.

Available helpers:

```ss
$First
$Last
$Pos
$EvenOdd
$TotalItems
```

These are available on both the element and the element controller.

Example:

```ss
<section class="element element--$EvenOdd<% if $First %> element--first<% end_if %>">
    $ElementForTemplate
</section>
```

## Rendering An Area

Use the area directly:

```ss
$ContentArea
```

or loop elements manually when you need custom wrapping:

```ss
<% if $ContentArea.Elements %>
    <div class="content-elements">
        <% loop $ContentArea.Elements %>
            $Me
        <% end_loop %>
    </div>
<% end_if %>
```

Prefer direct area rendering unless the page template really needs to control the loop.

## Rendering A Nested Area

```ss
<% if $BodyArea %>
    <div class="block-group__body">
        $BodyArea
    </div>
<% end_if %>
```

The nested area's elements still receive the correct current area and top container context.

## Template Naming Guidance

Use the narrowest template variant only when it is genuinely area-specific.

Good uses:

- a card element has different markup in a sidebar
- a hero aside has different wrapper markup
- nested group elements need tighter markup than page-level blocks

Avoid creating area-specific templates for minor spacing differences. Use CSS classes or element config for that.
