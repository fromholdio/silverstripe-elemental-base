# Upstream Contribution Opportunities

Last reviewed: 2026-06-16

Upstream reviewed: `silverstripe/silverstripe-elemental` `6.2.1` (`6966f0f` on the `6.2` branch).

This document identifies elemental-base ideas that could be contributed back to `silverstripe/silverstripe-elemental`. It is not a porting plan. Elemental Base is not a fork, and most useful upstream work would need to be reimplemented against Elemental's current architecture, split into small branches, and shaped around upstream's BC and product direction.

The current upstream module has moved in several directions that overlap with historical elemental-base concerns. In particular, current Elemental supports multiple `ElementalArea` relations, non-page owners better than older versions, top-page caching extensions, cross-record/cross-area move forms, richer summary schema fields, and several recent inline-editor fixes. The opportunities below try to account for that current state rather than describing old limitations as if they still fully apply.

## Executive Summary

The best first candidates are small, evidence-backed fixes or additive APIs:

1. Return 404s instead of 500s from the stock front-end element route.
2. Harden and extend non-page CMS edit-link resolution.
3. Use `TopPageID` in `ElementalArea::getOwnerPage()` for the performance case now reported upstream.
4. Add an advanced/full edit action for inline-editable blocks.
5. Add first-class PHP helpers for block summary content and thumbnail data.
6. Add clearer area-level type and field-placement configuration.
7. Improve ownership/publishing/deletion docs and tests around `publishRecursive()`, `owns`, and `cascade_deletes`.

The most valuable larger ideas are likely to need an RFC-style conversation first:

1. A named-area configuration contract.
2. Area-aware element routes.
3. Nested area/group traversal.
4. Provider/shared elements as an alternative to virtual clone records.
5. A fuller current/local/inherited area model.

## Current Upstream Demand

I reviewed the current open issue and PR queues. At review time GitHub showed 106 open issues and 16 open PRs for `silverstripe/silverstripe-elemental`.

Relevant open issues and PRs include:

- Inline save/validation stability: [#1435](https://github.com/silverstripe/silverstripe-elemental/issues/1435), [#1436](https://github.com/silverstripe/silverstripe-elemental/pull/1436), [#1183](https://github.com/silverstripe/silverstripe-elemental/issues/1183), [#1264](https://github.com/silverstripe/silverstripe-elemental/issues/1264), [#1266](https://github.com/silverstripe/silverstripe-elemental/issues/1266), [#815](https://github.com/silverstripe/silverstripe-elemental/issues/815), [#868](https://github.com/silverstripe/silverstripe-elemental/issues/868).
- Non-page owners and edit links: [#1407](https://github.com/silverstripe/silverstripe-elemental/issues/1407), [#1399](https://github.com/silverstripe/silverstripe-elemental/issues/1399), [#871](https://github.com/silverstripe/silverstripe-elemental/issues/871), [#778](https://github.com/silverstripe/silverstripe-elemental/issues/778).
- Multiple areas, field placement, and authoring clarity: [#752](https://github.com/silverstripe/silverstripe-elemental/issues/752), [#758](https://github.com/silverstripe/silverstripe-elemental/issues/758), [#715](https://github.com/silverstripe/silverstripe-elemental/issues/715), [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880), [#107](https://github.com/silverstripe/silverstripe-elemental/issues/107).
- Top-page and Fluent integration: [#1415](https://github.com/silverstripe/silverstripe-elemental/issues/1415), [#1257](https://github.com/silverstripe/silverstripe-elemental/issues/1257), [#1258](https://github.com/silverstripe/silverstripe-elemental/issues/1258), [#1259](https://github.com/silverstripe/silverstripe-elemental/pull/1259).
- Publishing, archiving, and history: [#666](https://github.com/silverstripe/silverstripe-elemental/issues/666), [#804](https://github.com/silverstripe/silverstripe-elemental/issues/804), [#1051](https://github.com/silverstripe/silverstripe-elemental/issues/1051), [#756](https://github.com/silverstripe/silverstripe-elemental/issues/756), [#470](https://github.com/silverstripe/silverstripe-elemental/issues/470).
- Summary, anchors, and link handling: [#1418](https://github.com/silverstripe/silverstripe-elemental/issues/1418), [#582](https://github.com/silverstripe/silverstripe-elemental/issues/582), [#1320](https://github.com/silverstripe/silverstripe-elemental/pull/1320), [#195](https://github.com/silverstripe/silverstripe-elemental/issues/195), [#689](https://github.com/silverstripe/silverstripe-elemental/issues/689), [#506](https://github.com/silverstripe/silverstripe-elemental/issues/506).
- Template loop helper parity: [#1274](https://github.com/silverstripe/silverstripe-elemental/issues/1274), [#1275](https://github.com/silverstripe/silverstripe-elemental/pull/1275), [#1221](https://github.com/silverstripe/silverstripe-elemental/issues/1221).
- Nested/grouped blocks and broader architecture: [#159](https://github.com/silverstripe/silverstripe-elemental/issues/159), [#845](https://github.com/silverstripe/silverstripe-elemental/issues/845), [#347](https://github.com/silverstripe/silverstripe-elemental/issues/347), [#768](https://github.com/silverstripe/silverstripe-elemental/issues/768).

The upstream docs also now cover additional areas, `publishRecursive()`, `owns`, `cascade_deletes`, `cascade_duplicates`, and manual per-area type restriction: [Elemental advanced setup](https://docs.silverstripe.org/en/6/optional_features/elemental/advanced_setup/). Any upstream PR should acknowledge that docs baseline.

## Opportunity Matrix

| Candidate | Shape | Evidence | Elemental Base reference | Scope | Likelihood |
| --- | --- | --- | --- | --- | --- |
| Route missing elements as 404s | Replace `user_error()` paths in `ElementalContentControllerExtension::handleElement()` with HTTP errors, with tests. | [#927](https://github.com/silverstripe/silverstripe-elemental/issues/927). | `_config/config.yml` disables `element/$ID`; `ElementsRouter` returns 404s. | Small bug fix. | High. |
| Non-page edit-link resolver | Add a clearer resolver/extension hook for element CMS links, plus hard failure or useful diagnostics when a non-page owner cannot produce a CMS edit link. | [#1407](https://github.com/silverstripe/silverstripe-elemental/issues/1407), [#1399](https://github.com/silverstripe/silverstripe-elemental/issues/1399), [#871](https://github.com/silverstripe/silverstripe-elemental/issues/871). | `EvoElementTrait::getCMSEditLink()`, `getElementCMSEditLink()`, `updateEvoCMSEditLink`. | Small to medium API hardening. | Medium-high. |
| Use `TopPageID` in owner lookup | Let `ElementalArea::getOwnerPage()` use cached top-page data before expensive relation scanning when available and safe. | [#1415](https://github.com/silverstripe/silverstripe-elemental/issues/1415). | Elemental Base has top-container/top-page context helpers, though not this exact upstream fix. | Small performance fix. | High. |
| Advanced edit action for inline blocks | Add a configurable action/menu item to open the full GridField detail edit form from an inline-editable block. | [#757](https://github.com/silverstripe/silverstripe-elemental/issues/757), [#403](https://github.com/silverstripe/silverstripe-elemental/issues/403), [#506](https://github.com/silverstripe/silverstripe-elemental/issues/506). | `BaseElementExtension::isAdvancedEditEnabled()`, `AdvancedEditLink()`, `client/src/components/ElementActions.js`. | Medium UX/API enhancement. | Medium. |
| Action menu for non-inline blocks | Decouple action-menu visibility from inline expandability so publish/unpublish/archive can be available for non-inline blocks. | [#403](https://github.com/silverstripe/silverstripe-elemental/issues/403). | Custom `ElementActions` always renders the edit-form action and keeps actions separate from inline tabs. | Medium client change. | Medium. |
| Inline field API | Add a supported `getInlineCMSFields()`-style hook and have tab discovery use the inline field list rather than full `getCMSFields()`. | [#1183](https://github.com/silverstripe/silverstripe-elemental/issues/1183), [#1264](https://github.com/silverstripe/silverstripe-elemental/issues/1264), [#1266](https://github.com/silverstripe/silverstripe-elemental/issues/1266), [#815](https://github.com/silverstripe/silverstripe-elemental/issues/815). | `EvoEditFormFactory`, `EvoElementTabProvider`, `BaseElementExtension::getInlineCMSFields()`. | Medium API and test work. | Medium-low unless split narrowly. |
| Summary helper API | Add convenience methods for summary parts and summary image data rather than requiring full `provideBlockSchema()` overrides. | [#1418](https://github.com/silverstripe/silverstripe-elemental/issues/1418), [#582](https://github.com/silverstripe/silverstripe-elemental/issues/582). | `getInlineCMSSummaryParts()`, `updateInlineCMSImage()`, `updateBlockSchema()`. | Small additive API. | Medium-high. |
| Area-level element type config | Add first-class per-area allowed/disallowed type config, or make `ElementalAreaField` construction easier to use safely. | [#752](https://github.com/silverstripe/silverstripe-elemental/issues/752), [#758](https://github.com/silverstripe/silverstripe-elemental/issues/758). | `EvoElementalArea::$element_classes`, `getValidElementClasses()`, container area config. | Medium. | Medium. |
| Area field placement config | Add supported placement config for area fields in CMS field lists. | [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880). | `ElementalAreasContainer::updateCMSFields()` with `cms_fields` placement config. | Small to medium. | Medium-high if modernised for `6`. |
| Publish with blocks action | Add or document a page/container action that explicitly calls `publishRecursive()` for the owner and all configured areas. | [#715](https://github.com/silverstripe/silverstripe-elemental/issues/715), [#804](https://github.com/silverstripe/silverstripe-elemental/issues/804), [#756](https://github.com/silverstripe/silverstripe-elemental/issues/756), [#1051](https://github.com/silverstripe/silverstripe-elemental/issues/1051). | `ElementalAreasContainer::doPublishWithAreas()`. | Medium, with product-direction questions. | Medium-low for code, high for docs/tests. |
| Cascade delete guidance/tests | Improve docs and tests for `cascade_deletes` on area relations, and possibly add opt-in config for owned page areas. | [#666](https://github.com/silverstripe/silverstripe-elemental/issues/666), [#804](https://github.com/silverstripe/silverstripe-elemental/issues/804). | Elemental Base docs/config consistently include `cascade_deletes`; `EvoElementalArea` cascades elements. | Small docs/tests, medium code. | High for docs/tests. |
| Sort repair/normalisation | Add a safe normalisation path for duplicate or corrupt sort values. | [#1046](https://github.com/silverstripe/silverstripe-elemental/issues/1046), [#934](https://github.com/silverstripe/silverstripe-elemental/issues/934). | `handleEmptySortValue()` only handles missing sort values, so this is inspired by adjacent behaviour rather than directly solved. | Medium bug fix. | Medium. |
| Template loop helper parity | Support `IsFirst`/`IsLast` and `$startIndex` on `Pos()` in a major-compatible way. | [#1274](https://github.com/silverstripe/silverstripe-elemental/issues/1274), [#1275](https://github.com/silverstripe/silverstripe-elemental/pull/1275), [#1221](https://github.com/silverstripe/silverstripe-elemental/issues/1221). | `EvoElementTrait::First()`, `Last()`, `Pos()`, `EvenOdd()`, `TotalItems()`. | Small API update. | High, but existing PR should probably be supported rather than duplicated. |
| Better anchors | Improve human-readable anchor titles, area-level anchor enablement, and possibly scoped/provider-aware anchor generation. | [#1320](https://github.com/silverstripe/silverstripe-elemental/pull/1320), [#195](https://github.com/silverstripe/silverstripe-elemental/issues/195), [#689](https://github.com/silverstripe/silverstripe-elemental/issues/689). | `EvoElementalArea::getAnchorsInArea()`, area `is_anchors_enabled`, provider-aware anchors. | Small to medium if split. | Medium. |
| Area-aware element routes | Add an optional route mode with area segment and handled-area restrictions. | [#927](https://github.com/silverstripe/silverstripe-elemental/issues/927), [#506](https://github.com/silverstripe/silverstripe-elemental/issues/506), [#960](https://github.com/silverstripe/silverstripe-elemental/issues/960). | `ElementsRouter`, `EvoElementController::HandlerLink()`. | Large unless reduced to optional API. | Low-medium. |
| Named area contract | Add a first-class named-area configuration model for relation name, field name, URL segment, CMS placement, allowed types, and enablement. | [#752](https://github.com/silverstripe/silverstripe-elemental/issues/752), [#758](https://github.com/silverstripe/silverstripe-elemental/issues/758), [#715](https://github.com/silverstripe/silverstripe-elemental/issues/715), [#347](https://github.com/silverstripe/silverstripe-elemental/issues/347). | `ElementalAreasContainer`, `EvoElementalArea` current/local name and relation helpers. | RFC-level. | Low without prior maintainer alignment. |
| Nested area/group traversal | Let blocks own areas and have traversal/search/rendering handle nested containers deliberately. | [#159](https://github.com/silverstripe/silverstripe-elemental/issues/159), [#845](https://github.com/silverstripe/silverstripe-elemental/issues/845), [#1338](https://github.com/silverstripe/silverstripe-elemental/issues/1338). | `EvoElementalArea::getAllElements()`, current area/container traversal, nested routing. | RFC-level. | Low. |
| Provider/shared elements | Introduce a provider contract for rendering real shared elements in the current context without virtual clone records. | [#768](https://github.com/silverstripe/silverstripe-elemental/issues/768), [#159](https://github.com/silverstripe/silverstripe-elemental/issues/159). | `BaseElementExtension::provideElements()`, provider/current area context. | RFC-level or separate module. | Low. |

## Detailed Recommendations

### 1. Front-End Element Route 404s

Current upstream `ElementalContentControllerExtension::handleElement()` still uses `user_error()` for missing IDs, missing area config, and unfound elements. The open issue [#927](https://github.com/silverstripe/silverstripe-elemental/issues/927) asks for 404s instead of server errors.

Elemental Base handles this by disabling the stock `element/$ID` route and replacing it with `ElementsRouter`, which returns `httpError(404)` for invalid area/element cases. Upstream probably does not need the whole area-aware router to fix this. A small PR could:

- return `httpError(404)` for missing element IDs and unfound elements
- preserve clearer diagnostics in dev mode if maintainers want that
- add controller tests for invalid ID, missing area, and nested unfound element cases

This is one of the cleanest contribution candidates.

### 2. Non-Page CMS Edit Links

Upstream has recently improved non-page owner handling. Closed [#1399](https://github.com/silverstripe/silverstripe-elemental/issues/1399) confirms a recent fix for inline-editable blocks with non-SiteTree parents. Closed [#871](https://github.com/silverstripe/silverstripe-elemental/issues/871) also shows the older ModelAdmin/DataObject pain point has been partly addressed.

There is still an open follow-up, [#1407](https://github.com/silverstripe/silverstripe-elemental/issues/1407), for the case where a non-SiteTree parent has an empty CMS edit link. Elemental Base's useful contribution is not the exact URL logic, but the explicit resolver contract:

- ask the owning container whether it can resolve an element-specific CMS edit link
- fall back to a standard construction when possible
- expose an extension hook for final project-specific adjustment
- fail clearly when no usable owner edit link exists

Possible upstream PRs:

1. A small PR for [#1407](https://github.com/silverstripe/silverstripe-elemental/issues/1407): throw a useful exception or return a controlled validation error when a direct edit link cannot be generated.
2. A follow-up API PR: add a public/protected resolver method or extension hook that receives the element, area, relation name, owner, and direct-link flag.
3. Tests covering SiteTree, ModelAdmin-managed DataObject, SiteConfig-like singleton owner, inline and non-inline elements.

This would reduce the need for elemental-base to override as much of `getCMSEditLink()` in projects where upstream's route shape is otherwise acceptable.

### 3. TopPageID Owner Lookup

Open issue [#1415](https://github.com/silverstripe/silverstripe-elemental/issues/1415) reports that `ElementalArea::getOwnerPage()` does not use `TopPageID`, causing measurable CMS load cost on large sites with multiple areas. The proposed fix is simple in spirit: prefer the cached top-page relation when it is populated and valid.

Elemental Base's top-container helpers solve a broader contextual problem, but upstream has a direct issue with a small likely fix. This would be worth PR'ing independently of larger named-area work.

Care needed:

- Confirm it is safe only for SiteTree top pages, not arbitrary DataObject containers.
- Preserve existing owner discovery for non-page owners and areas whose top page is missing.
- Add regression tests that show fewer/safer lookup paths where possible.

### 4. Advanced Edit for Inline Blocks

Open issue [#757](https://github.com/silverstripe/silverstripe-elemental/issues/757) asks for an "Advanced" or "More" action that lets an inline-editable block open its full dedicated edit form for relationship-heavy cases. This maps directly to elemental-base's advanced edit affordance.

The smallest upstreamable shape is:

- Add a block config flag such as `show_advanced_edit_action` or a schema action flag.
- Add a menu action only when a direct edit link can be generated.
- If the inline form is dirty, either prompt or save first. This needs alignment with the current inline save model and [#1436](https://github.com/silverstripe/silverstripe-elemental/pull/1436).
- Keep the action label and UX modest: "Edit details" or "Advanced edit".

This could be paired with [#403](https://github.com/silverstripe/silverstripe-elemental/issues/403), which asks for more flexible action rendering and actions on non-inline blocks.

Acceptance is plausible, but maintainers may prefer continued investment in inline editing. The PR should be framed as a pragmatic escape hatch for fields that are not suitable for inline editing, not as a competing editing model.

### 5. Action Menu Visibility and Non-Inline Blocks

Elemental currently treats inline expandability as a major factor in action-menu rendering. [#403](https://github.com/silverstripe/silverstripe-elemental/issues/403) asks to separate those concerns so publish/unpublish/archive actions can appear for non-inline blocks, while save remains inline-only.

Elemental Base's custom `ElementActions` is heavier than upstream needs, but it demonstrates the separation:

- inline tab actions are one category
- edit/full-edit link is another
- publish/unpublish/archive/duplicate actions are separate children

An upstream PR could add a schema/config property for action visibility and keep defaults BC-friendly.

### 6. Inline CMS Field API

Elemental Base's `getInlineCMSFields()` is one of the more useful ideas, but it touches a sensitive area. Upstream has many inline-editing issues, including [#1183](https://github.com/silverstripe/silverstripe-elemental/issues/1183), [#1264](https://github.com/silverstripe/silverstripe-elemental/issues/1264), [#1266](https://github.com/silverstripe/silverstripe-elemental/issues/1266), [#815](https://github.com/silverstripe/silverstripe-elemental/issues/815), and recent [#1435](https://github.com/silverstripe/silverstripe-elemental/issues/1435)/[#1436](https://github.com/silverstripe/silverstripe-elemental/pull/1436).

The upstreamable idea should be narrow:

- Add a supported method/hook for inline-only fields.
- Have `ElementTabProvider` inspect the same inline field list used to build the inline form.
- Keep `getCMSFields()` as the full detail form contract.
- Do not try to solve every inline-form bug in the same PR.

This may be best proposed after [#1436](https://github.com/silverstripe/silverstripe-elemental/pull/1436) lands or is otherwise resolved, because batch save behaviour is currently in flux.

### 7. Summary Schema Convenience

Upstream already has a React summary component that accepts `content`, `fileUrl`, and `fileTitle`, and `BaseElement::provideBlockSchema()` includes `content` from `getSummary()`. [#1418](https://github.com/silverstripe/silverstripe-elemental/issues/1418) asks about HTML in summaries, and older [#582](https://github.com/silverstripe/silverstripe-elemental/issues/582) asks for better summary support for other block types.

Elemental Base can inspire a small additive PHP API:

- `getInlineCMSSummaryParts(): array`
- `getInlineCMSSummary(): ?string`
- `getInlineCMSImage(): ?Image`
- `updateInlineCMSImage(?Image &$image)`
- fill `content`, `fileURL`/`fileUrl`, and `fileTitle` in `updateBlockSchema()`

The HTML question needs caution. Upstream's React component currently renders summary content as text, which is safe. A PR should probably avoid raw HTML rendering unless there is a sanitised, explicitly trusted schema path. A safer contribution would be clearer docs and convenience methods for text summaries and thumbnails.

### 8. Area-Level Type Restrictions

Upstream supports owner-level `allowed_elements`/`disallowed_elements`, and the docs show manually fetching an `ElementalAreaField` and calling `setTypes()` for different area relations. [#752](https://github.com/silverstripe/silverstripe-elemental/issues/752) and [#758](https://github.com/silverstripe/silverstripe-elemental/issues/758) show that this remains a rough edge.

Elemental Base's useful concept is area-level type config with inherited allow/disallow behaviour. A realistic upstream PR could start smaller:

- Add config keyed by area relation name, for example `elemental_area_types.SidebarElementalArea.allowed`.
- Apply it when `ElementalAreasExtension::updateCMSFields()` creates each `ElementalAreaField`.
- Keep existing owner-level `allowed_elements` as the default.
- Add docs showing two areas with different block sets.

This is a good medium-sized PR because it solves an old authoring problem without requiring the full named-area architecture.

### 9. CMS Field Placement

Open PR [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880) attempted an `insert_before_field_name` config in 2021 and is still open against an old branch. Elemental Base handles placement through richer `cms_fields` config and `fromholdio/silverstripe-cms-fields-placement`.

An upstream PR should probably be smaller than Elemental Base's integration:

- Modernise [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880) for the `6` branch.
- Support at least `insertBefore` or `insertAfter` per owner class and possibly per area relation.
- Add docs for blog-like page types and multi-area pages.

This is likely acceptable if the API remains simple.

### 10. Publishing, Archiving, and Ownership

The upstream docs already tell developers to use `publishRecursive()` for programmatic publishing and to configure `owns` and `cascade_deletes`. Still, open issues show this remains a practical source of bugs: [#715](https://github.com/silverstripe/silverstripe-elemental/issues/715), [#804](https://github.com/silverstripe/silverstripe-elemental/issues/804), [#1051](https://github.com/silverstripe/silverstripe-elemental/issues/1051), and [#666](https://github.com/silverstripe/silverstripe-elemental/issues/666).

Elemental Base adds a "Publish with blocks" CMS action and explicitly publishes local areas. Upstream might reject that as product direction, especially because [#756](https://github.com/silverstripe/silverstripe-elemental/issues/756) argues about block-based publish defaults. Better PR splits:

1. Docs/tests first: ensure all examples for additional areas include `owns`, `cascade_duplicates`, and, where appropriate, `cascade_deletes`.
2. Add regression tests for multi-area publish behaviour.
3. Consider an opt-in action only after maintainers agree on UI direction.

This is an area where contribution could reduce project confusion even if no new feature is accepted.

### 11. Cascade Delete Cleanup

Issue [#666](https://github.com/silverstripe/silverstripe-elemental/issues/666) has fresh 2025 comments that orphaned areas/elements still accumulate. Elemental Base docs consistently recommend `cascade_deletes` for locally owned areas.

A low-risk upstream docs PR could:

- Add `cascade_deletes` to examples that already show `owns` and `cascade_duplicates`.
- Explain the restore trade-off if that is still relevant.
- Add a short "owned area lifecycle" section.

A code PR might be more contentious because automatic cascade deletes can surprise projects with shared or non-standard ownership. Docs/tests are the natural first move.

### 12. Sort Repair

Elemental Base handles empty sort values on write. Upstream already has `ensureSortSet()` for new records, but [#1046](https://github.com/silverstripe/silverstripe-elemental/issues/1046) describes duplicate sort values that can leave blocks stuck.

This is not an elemental-base feature to copy directly, but it is a related improvement worth considering:

- Add a repair service or task that normalises sort values for a single area.
- Call it defensively before reorder operations if duplicates are detected.
- Add tests for duplicate `Sort = 1` cases.

The benefit is clear, but the implementation should be upstream-native.

### 13. Template Loop Helpers

Elemental Base provides `First()`, `Last()`, `Pos()`, `EvenOdd()`, and `TotalItems()` by assigning extra data from the rendered area list. Upstream already has open [#1274](https://github.com/silverstripe/silverstripe-elemental/issues/1274) and PR [#1275](https://github.com/silverstripe/silverstripe-elemental/pull/1275) for `IsFirst()`/`IsLast()`, plus [#1221](https://github.com/silverstripe/silverstripe-elemental/issues/1221) for `Pos($startIndex)` parity.

Recommendation:

- Do not open a competing `IsFirst()`/`IsLast()` PR while [#1275](https://github.com/silverstripe/silverstripe-elemental/pull/1275) exists.
- Offer review, tests, or a refreshed branch if the original contributor is not continuing.
- Consider a separate `Pos($startIndex = 1)` PR against the correct major branch if maintainers confirm it is acceptable.

### 14. Anchors

Upstream already scans HTML fields for anchors and adds block anchors to page anchors. Open PR [#1320](https://github.com/silverstripe/silverstripe-elemental/pull/1320) asks for human-readable anchor values. Elemental Base has a broader anchor model:

- per-area anchor enablement
- element-local anchor fields
- configured HTML fields to scan
- provider-aware anchor generation
- menu-visible element helpers

Recommended split:

1. Support or refresh [#1320](https://github.com/silverstripe/silverstripe-elemental/pull/1320) against the current branch with tests.
2. Add an issue/PR for configurable anchor field names if maintainers want more control than "all HTMLText fields".
3. Treat provider-aware anchors as part of a larger shared/provider RFC, not a standalone PR.

### 15. Area-Aware Element Routes

Elemental Base replaces `/element/<id>` with `/area/<area-segment>/<element-id>`, and `HandlerLink()` generates handler URLs in that context. This solves ambiguity when pages have multiple areas, nested areas, or provider/shared elements.

Upstream's stock route currently searches all elemental relations and nested elemental owners for an ID. That keeps URLs simple, but it cannot express area context.

Possible upstream route:

- First PR: fix 404 behaviour only.
- Later RFC or optional mode: add area-aware routes for projects that need disambiguation.
- Include handled-area restrictions so not every area is routable.
- Keep old route behaviour for BC unless a major version removes it.

This is valuable, but it should not be the first upstream PR because it is entangled with URL compatibility and routing expectations.

### 16. Named Area Contract

Elemental Base's named-area config is the core that ties together relation name, field name, URL segment, field placement, allowed types, current/local areas, anchors, menus, routes, and edit links.

Upstream currently discovers `ElementalArea` relations and lets developers manipulate fields/config manually. That is lighter, but the open issue history shows recurring friction around multiple areas.

This is worth discussing upstream only as an RFC:

- Define the smallest named-area config that would solve existing issues.
- Avoid trying to import the full elemental-base contract in one step.
- Start with per-area type config and field placement as proof points.

If accepted in some form, this would be one of the biggest ways to reduce elemental-base footprint. It is also one of the least likely to be accepted without careful prior alignment.

### 17. Nested Areas and Groups

Elemental Base treats nested area containers as part of normal traversal. Elements can own areas, nested elements receive current area/container context, and routing can descend through nested structures.

Upstream demand exists in broad form through [#159](https://github.com/silverstripe/silverstripe-elemental/issues/159) and specific nested crash/history issues such as [#845](https://github.com/silverstripe/silverstripe-elemental/issues/845). However, this is architectural work.

Recommendation:

- Do not start with a full nested-area PR.
- If contributing, begin with a small test/fix for a concrete nested issue.
- Use elemental-base as evidence that nested traversal needs explicit context, not as code to transplant.

### 18. Provider/Shared Elements

Elemental Base's provider contract lets a local element provide real elements from elsewhere, while those provided elements render in the current area/provider context. This avoids virtual clone records for many shared-content use cases.

Upstream has `silverstripe-elemental-virtual` in the ecosystem, and there are broad requests for an Elemental admin and layouts/groups. I did not find strong current issue demand for this exact provider model.

Recommendation:

- Keep this out of the first upstream batch.
- If proposed, do it as an RFC or separate experimental module.
- Frame it as "render shared content in context" rather than as a replacement for virtual records.

### 19. Current/Local/Inherited Areas

Elemental Base has a richer distinction between local areas, current areas, merged/replaced areas, inherited/current content, and provider context. This is central to this module, but it is much broader than any single open upstream issue.

Possible contribution path:

- Identify one narrow pain point, such as template context, search indexing with no current controller ([#1338](https://github.com/silverstripe/silverstripe-elemental/issues/1338)), or nested routing.
- PR that single fix.
- Avoid proposing the entire current/local area model unless maintainers invite an RFC.

### 20. Lower-Priority Additive Ideas

These are technically upstreamable but have weaker current issue evidence:

- `isElementEmpty()` or `updateIsElementEmpty` so empty blocks do not render.
- Element title/name separation for cleaner CMS labels and anchor names.
- Template selection by area name/type.
- Per-element permission code helpers.
- Area menu helpers such as `getMenuElements()`.

These should probably stay in elemental-base unless a real upstream issue or project case is opened first.

## Recommended Sequencing

### Phase 1: Low-Risk Fixes and Existing PR Support

1. PR route 404 behaviour for [#927](https://github.com/silverstripe/silverstripe-elemental/issues/927).
2. PR `TopPageID` lookup for [#1415](https://github.com/silverstripe/silverstripe-elemental/issues/1415).
3. Support or refresh existing PRs where useful: [#1436](https://github.com/silverstripe/silverstripe-elemental/pull/1436), [#1275](https://github.com/silverstripe/silverstripe-elemental/pull/1275), [#1320](https://github.com/silverstripe/silverstripe-elemental/pull/1320), [#1259](https://github.com/silverstripe/silverstripe-elemental/pull/1259), [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880).
4. Add docs/tests for owned area lifecycle and cascade deletes around [#666](https://github.com/silverstripe/silverstripe-elemental/issues/666).

### Phase 2: CMS Authoring APIs

1. Non-page edit-link resolver/hook for [#1407](https://github.com/silverstripe/silverstripe-elemental/issues/1407).
2. Advanced edit action for [#757](https://github.com/silverstripe/silverstripe-elemental/issues/757).
3. Action-menu visibility for [#403](https://github.com/silverstripe/silverstripe-elemental/issues/403).
4. Summary helper API for [#1418](https://github.com/silverstripe/silverstripe-elemental/issues/1418).
5. Area type config and CMS field placement for [#752](https://github.com/silverstripe/silverstripe-elemental/issues/752) and [#880](https://github.com/silverstripe/silverstripe-elemental/pull/880).

### Phase 3: Larger Architecture Conversations

1. Named area config RFC.
2. Optional area-aware routing.
3. Nested area/group traversal.
4. Provider/shared elements.
5. Current/local/inherited area context.

## Dependencies Between Candidates

- Advanced edit depends on robust direct CMS edit-link generation.
- Action-menu visibility and advanced edit should probably share one client schema/action mechanism.
- Inline field API affects `EditFormFactory`, `ElementTabProvider`, validation, and save-all behaviour, so it should wait until current inline save PRs settle.
- Area-level type config and field placement can be done without the full named-area contract, but they are stepping stones toward it.
- Area-aware routes depend on stable area identity. They become much easier if named-area config exists.
- Provider/shared elements depend on current/local area context and are best treated as architectural work.
- Publish/delete lifecycle changes depend on clear ownership rules. Docs and tests should lead.

## What Could Reduce Elemental Base Footprint

If accepted upstream, these would most directly reduce overrides or local maintenance:

- Route 404 fix: reduces the need to disable the stock `element/$ID` route solely for error behaviour.
- Edit-link resolver: reduces custom `getCMSEditLink()` handling for ModelAdmin, SiteConfig, and custom DataObject containers.
- Advanced edit/action visibility: reduces client bundle replacement pressure.
- Summary helper API: reduces PHP schema-extension boilerplate and possible client summary replacement.
- Inline field API: reduces the need to override `EditFormFactory` and `ElementTabProvider`.
- Area type/placement config: reduces custom area field assembly for simple multi-area projects.
- Named area config, if ever accepted: would reduce a large part of elemental-base's architectural delta.

## Suggested Branches If We Proceed

These names are only suggestions for future upstream work:

- `upstream/route-404`
- `upstream/top-page-owner-lookup`
- `upstream/cms-edit-link-resolver`
- `upstream/advanced-edit-action`
- `upstream/block-summary-helpers`
- `upstream/area-type-config`
- `upstream/field-placement-config`
- `upstream/cascade-delete-docs`
- `upstream/sort-normalisation`

## Final Assessment

The highest-value contribution strategy is not to lead with elemental-base's biggest architectural opinions. Start by contributing the small things that current Elemental users are already asking for and that current upstream code can absorb: 404s, edit-link hardening, top-page lookup, docs/tests, summary helpers, and action menu improvements.

After that, the strongest medium-sized PRs are area type config, field placement, and advanced edit. Those are concrete, developer-facing, and supported by old but still-open issues.

The larger elemental-base concepts are still valuable, but they should be framed as RFC material or as several years of production evidence for why named context matters once Elemental is used beyond one page/main-content area.
