# Documentation

`silverstripe-elemental-base` is a base layer for building named, contextual, multi-area block systems on top of `dnadesign/silverstripe-elemental`.

The docs assume you already know the basics of Silverstripe CMS, `DataObject` configuration, templates, and Elemental blocks. They focus on what this module adds and how to use those additions without losing the underlying Elemental model.

## Contents

1. [Installation](01_installation.md)
2. [Core concepts](02_core-concepts.md)
3. [Configuring areas](03_configuring-areas.md)
4. [Creating elements](04_creating-elements.md)
5. [Current, local, and inherited areas](05_current-local-and-inherited-areas.md)
6. [Nested areas](06_nested-areas.md)
7. [Shared and provider elements](07_shared-and-provider-elements.md)
8. [Routing and controllers](08_routing-and-controllers.md)
9. [Templates and rendering](09_templates-and-rendering.md)
10. [CMS authoring experience](10_cms-authoring-experience.md)
11. [Anchors and navigation](11_anchors-and-navigation.md)
12. [Publishing, versioning, and duplication](12_publishing-versioning-and-duplication.md)
13. [Permissions](13_permissions.md)
14. [Fromholdio integrations](14_fromholdio-integrations.md)

## Reading Path

For a first implementation, read:

- [Core concepts](02_core-concepts.md)
- [Configuring areas](03_configuring-areas.md)
- [Creating elements](04_creating-elements.md)
- [Templates and rendering](09_templates-and-rendering.md)

For an existing project that already uses Elemental, read:

- [Current, local, and inherited areas](05_current-local-and-inherited-areas.md)
- [Routing and controllers](08_routing-and-controllers.md)
- [CMS authoring experience](10_cms-authoring-experience.md)
- [Publishing, versioning, and duplication](12_publishing-versioning-and-duplication.md)

For richer composition patterns, read:

- [Nested areas](06_nested-areas.md)
- [Shared and provider elements](07_shared-and-provider-elements.md)
- [Anchors and navigation](11_anchors-and-navigation.md)

## Naming Notes

The module uses `Evo` prefixes in class names such as `EvoElementalArea` and `EvoBaseElement`. Treat those as the public classes provided by this module, not as a separate product concept.

## Upstream Elemental Notes

Where these docs compare this module to upstream `dnadesign/silverstripe-elemental`, they refer to the current Elemental 6 line. Upstream Elemental now supports and documents multiple areas, so these docs avoid saying otherwise. The important difference is that this module builds a named-area contract around areas, elements, routing, templates, inheritance, and CMS context.
