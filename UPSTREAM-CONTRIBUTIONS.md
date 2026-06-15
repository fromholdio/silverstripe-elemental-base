# Contributing elemental-base improvements upstream

An assessment of which elemental-base features and fixes could be contributed back to
`dnadesign/silverstripe-elemental`, cross-referenced against demand in the upstream
issue tracker.

> **Status:** planning notes, not a commitment. elemental-base is **not** a fork of
> elemental; landing any of this upstream means forking elemental, extracting the
> relevant change with its own integration work, and opening a focused PR per item.
>
> **Reviewed:** June 2026, against `dnadesign/silverstripe-elemental` **6.2.1** and its
> then-open issues (106) and PRs (16). Issue references are a point-in-time snapshot.

## How demand was gauged

GitHub reaction counts on this repo are low across the board (the single most-upvoted
open issue sits at 👍7), so 👍 alone is a weak signal. The stronger signals are
**comment volume** (💬) and, especially, **recurrence** — the same problem raised across
several issues over multiple years. Where an issue is already **closed**, it still
counts as evidence the problem is real and known (e.g. the ModelAdmin edit-link issues
below).

A repeated caveat throughout: upstream demand usually exists for the **problem**
elemental-base solves, not for elemental-base's specific **solution** (which is bound up
in its named-area contract). The contribution path is therefore almost always "extract a
targeted fix for the issue," not "port the elemental-base class."

## Demand snapshot

The busiest themes in the open tracker, and how they relate to elemental-base:

| Theme | Representative issues | elemental-base relevance |
| --- | --- | --- |
| Editing elements on non-page / ModelAdmin records | #718✓, #871✓, #1407, #778, #166, #946 | **Direct** — robust `getCMSEditLink()` + hooks |
| Multiple areas: rough edges (publish, UX, moving) | #715, #758, #107 | **Direct** — named multi-area model |
| Publishing / lifecycle behaviour | #756 (👍7💬23), #666, #804, #508, #470 | Partial — publish-with-blocks, cascade-delete guidance |
| Advanced edit while inline-editing | #757 (👍2💬6) | **Direct** — advanced-edit link |
| Block summary / actions UX | #582 (💬9), #1418, #403 | **Direct** — `ElementSummary` / `ElementActions` |
| TopPage / `$Top` traversal robustness | #505 (💬11), #1415, #1257, #1258, #1021 | **Direct** — `getTop*()` helpers |
| Element controller has no request | #960, #1338 (💬11) | **Direct** — request-before-`doInit()` |
| Block anchors quality | PR #1320, #195, #689 (💬12) | **Direct** — anchor system |
| CMS field placement around areas | PR #880, #758 | Partial — `cms_fields` placement |
| ✓ = closed issue, cited as evidence the problem is real | | |

Themes with **little or no** upstream demand: reusable/shared/provider elements (body
matches were incidental noise), hide-empty-elements, per-element-class permissions,
in-page menu / table-of-contents, and a separate CMS-only "name" field.

---

## Recommendations

Tiers are ordered by **(demand × extractability)** — i.e. how clean a standalone fix is
*and* how much the tracker shows people want it. Acceptance likelihood is noted per item
but is **not** what determines inclusion or order.

### Tier A — strong, recurring demand + a cleanly extractable fix

**A1. Element edit links on non-page / ModelAdmin records** *(elemental-base: `getCMSEditLink()`, `getElementCMSEditLink()` hook, `updateEvoCMSEditLink` hook, dynamic relation-name resolution)*
- **Demand:** recurring over years — closed #718 ("can't handle sub-URLs on
  `LeftAndMainFormRequestHandler`") and #871 ("Elemental Area can't easily be added to a
  non-Page DataObject and edited in a ModelAdmin"), plus open #1407 ("throw exception if
  non-sitetree parent CMSEditLink is empty"), #778 ("Could not find elemental area … on
  create new DataObject in ModelAdmin"), #166 ("has_one to an Element data object punts
  user to page 1", 💬5), #946 ("`getOwnerPage()` fails when SiteTree ClassName is not a
  table", 💬8).
- **What to PR:** make element edit-link resolution work when the owner is `SiteConfig`,
  a ModelAdmin-managed DataObject, or a ModelAdmin-managed page; stop hard-coding the
  `ElementalArea` relation name; add an owner-side `getElementCMSEditLink()` hook (or
  similar) for custom CMS sections. #1407 only asks for a *clear exception* — elemental-base
  goes further and resolves the link, so this comfortably over-delivers.
- **Acceptance:** **medium-high.** Addresses tracked issues; the hook API will need
  discussion. Pairs naturally with A2.

**A2. Stop hard-coding `'ElementalArea'` in relation-name resolution** *(elemental-base: dynamic `getRelationName()`, breadcrumb fix)*
- **Demand:** underlies A1; also surfaces in multi-area bugs (#715) and the breadcrumb
  assumptions noted in #531.
- **What to PR:** resolve the actual area relation for an element generically (works for
  renamed / multiple / non-page areas) instead of falling back to the literal
  `'ElementalArea'`. Small, and unblocks A1.
- **Acceptance:** **medium.** Clearly a latent multi-area bug; the fallback may be
  defended on backwards-compatibility grounds.

**A3. Element controller has the request available** *(elemental-base: set request before `doInit()`)*
- **Demand:** direct — #960 ("`ElementController::getRequest()` returns a
  `NullHTTPRequest`") and #1338 ("No current controller when indexing for search", 💬11).
- **What to PR:** set the current request on a freshly-created element controller before
  `doInit()`, so controllers rendered in-template can read GET/POST. Small, standalone.
- **Acceptance:** **medium.** Small and sensible; expect a request for a test.

**A4. Publishing with multiple areas** *(elemental-base: multi-area-aware publishing, publish-with-blocks)*
- **Demand:** #715 ("Cannot publish Elements if two ElementalAreas exist on the same
  page", 💬4) is a concrete multi-area publish bug; #756 ("Disable block-based publish by
  default", 👍7💬23) is the single most-engaged issue and shows publishing behaviour is
  contentious.
- **What to PR:** the targeted fix for #715 is the realistic contribution (publishing
  correctly across multiple areas). elemental-base's full publish-with-blocks action is
  **not** a strong candidate — upstream already cascades via `owns`, which elemental-base
  can't use because its areas have a polymorphic parent. Treat publish-with-blocks as
  elemental-base-specific; treat the multi-area publish *bug* as contributable.
- **Acceptance:** **medium** for the bug fix; **low** for the action.

### Tier B — good demand, moderate effort

**B1. "Advanced edit" link from inline editing** *(elemental-base: advanced-edit message/link, `is_advanced_edit_enabled`)*
- **Demand:** #757 ("Idea: 'Advanced' edit menu option if editing inline", 👍2💬6) is
  almost a verbatim description of the feature — inline-edit the simple fields, with a
  link out to the full edit form for relationships. #403 ("show actions on non in-line
  editable block", 👍1💬7) is adjacent.
- **What to PR:** the inline advanced-edit affordance. Depends on A1 to resolve the
  target link correctly across contexts.
- **Acceptance:** **medium.** It's a maintainer-filed idea; the implementation will need
  to match their React patterns.

**B2. Block summary / actions improvements** *(elemental-base: `ElementSummary`, `ElementActions`, `updateBlockSchema`)*
- **Demand:** #582 ("Refactor SummaryComponent to support other block types better", 💬9)
  is maintainer-filed; #1418 ("HTML in Component Summary", 💬3) and #403 are related.
- **What to PR:** a summary that shows image and/or text with a smarter empty state, fed
  by a schema hook. Frontend; must fit their component architecture.
- **Acceptance:** **medium.** Contained UX win, but they may have their own direction
  (note #582 is theirs).

**B3. TopPage / top-context traversal robustness** *(elemental-base: `getTopArea()`/`getTopContainer()`/`getTopPage()`)*
- **Demand:** recurring — #505 ("`$Top` doesn't work", 💬11), #1415 ("ElementalArea
  TopPageID is not used"), #1257 ("fixedTopPageID Public API has gaps"), #1258 ("Top page
  integration with Fluent"), #1021 ("Refactor TopPage").
- **What to PR:** specific fixes where elemental-base's traversal is more robust than the
  `TopPageID` mechanism. These are likely several small PRs, not one.
- **Acceptance:** **medium**, per fix. TopPage is actively being reworked upstream, so
  coordinate with their direction.

**B4. Cascade-delete of owned areas** *(elemental-base: `cascade_deletes` guidance)*
- **Demand:** #666 ("Elements are not deleted when their parent pages are deleted", 💬3),
  #804 ("Blocks are not archived with their parent page", 💬2).
- **What to PR:** this is mostly a defaults/guidance gap — upstream's areas extension
  documents `owns`/`cascade_duplicates` but not deletion cleanup. A PR could add sensible
  cascade-delete defaults (or docs) for area relations.
- **Acceptance:** **low-medium.** Defaults changes touch BC; might land better as docs.

### Tier C — some demand, or an existing PR to collaborate on (don't duplicate)

**C1. Better block anchors** — **open PR #1320** ("ENH: better anchors from blocks =>
provide human readable value") already moves toward elemental-base's readable-anchor
behaviour. Also #195, #689 (💬12). *Engage with #1320* rather than open a competing PR;
offer elemental-base's `AnchorName` + HTML-field harvesting as enhancements.

**C2. Configurable CMS field placement around areas** — **open PR #880** ("specify
location for Elemental field via custom insert-before config") and #758 (authors hacking
to put headings before areas). elemental-base solves this via `cms_fields` placement
(through `silverstripe-cms-fields-placement`). *Comment on #880* with the pattern; the
code itself isn't directly portable (separate module).

**C3. Position-helper ergonomics** — #1274 / **open PR #1275** ("`First()`/`Last()`
should be `IsFirst()`/`IsLast()`") and #1221 ("`$startIndex` param on `Pos`"). Upstream
*already* has `First/Last/Pos/EvenOdd/TotalItems`; these are naming/param tweaks.
elemental-base has nothing extra to add here — noted only so we don't propose redundant
helpers.

**C4. Forms / redirects inside elements** — #965 ("form submission issue"), #1435/#1436
(inline save-all hangs with multiple element forms), #890. elemental-base's area-scoped
routing + element-controller redirect handling are relevant, but the routing rewrite is
invasive (Tier D). A small, idiomatic "allow an element action to redirect" PR is the
extractable piece.

### Tier D — keep in elemental-base (little/no upstream demand, or too opinionated)

These are core to elemental-base's value but are unlikely upstream contributions —
either the tracker shows no real pull, or they're a philosophical divergence. Listed for
completeness.

- **Reusable / provider / shared elements** (`provideElements()`, merge/replace) — no
  genuine demand surfaced (matches were incidental); upstream already has the separate
  `elemental-virtual` module.
- **Hide-empty-elements** (`isElementEmpty()`) — no demand surfaced. Still a nice opt-in
  hook if ever proposed.
- **Per-element-class permission codes** — no demand surfaced.
- **In-page menu / "show in menus"** — no demand surfaced.
- **Separate CMS-only `Name` field** — no demand; upstream is invested in `Title` +
  `ShowTitle` (note #716, #660 are about Title behaviour, not a second field).
- **The named-area contract & everything on it** (`$elemental_areas`, polymorphic area
  parent, current/local inheritance, area-scoped routing, the CMS form restructure) — the
  large, dependent architectural cluster. The realistic venue for any of this is the
  open **RFC #347** ("Restructure data models to use more default relationship handling",
  👍5💬10) and the older #159 ("Manage block layouts and groups", 👍2💬14) — i.e. a
  design discussion, not a cold PR.

---

## Existing upstream PRs to engage with

Before opening anything, coordinate with these in-flight PRs so we collaborate rather
than collide:

- **#1320** — better block anchors (overlaps elemental-base anchors → C1).
- **#880** — configurable Elemental field placement (overlaps `cms_fields` → C2).
- **#1275** — `IsFirst()`/`IsLast()` helpers (→ C3).
- **#1259** — Top page configuration improvements (overlaps `getTop*()` → B3).

## Cross-cutting caveats

- **Extraction cost is real.** Most Tier A/B items are entangled with elemental-base's
  area-name contract, request/controller handling, or React overrides. Each needs to be
  re-expressed against upstream's relation-discovery model, with tests, before it's a
  credible PR.
- **Acceptance is genuinely uncertain.** Silverstripe maintainers favour targeted,
  BC-safe, tested fixes that map to tracked issues (Tier A/B), and are wary of new model
  fields, new config surface, and re-architecture (Tier D). The tiering reflects that
  reality but does not depend on it — every candidate is listed regardless.
- **Footprint payoff.** Each accepted upstream fix lets elemental-base drop the
  corresponding override (edit-link resolver, request-before-init, redirect handling,
  summary/actions overrides). Independently of any PR, note that 6.2.1 **already** ships
  `First/Last/Pos/EvenOdd/TotalItems` and `allowed_elements`/`disallowed_elements`/
  `stop_element_inheritance` — elemental-base reimplements both and could potentially
  shed code by leaning on upstream for the simple cases.

## Suggested first move

The cleanest "yes" is the **edit-link cluster (A1 + A2)**: it maps onto two already-closed
issues plus four open ones, over-delivers on what #1407 asks for, and is a coherent,
self-contained fix. Opening an issue that references #718/#871/#1407 to propose the
approach — before writing code — is the low-risk way to test maintainer appetite.

## Alternative PR shapes (brainstorm)

This section does **not** change the recommendations or tiers above. It is a non-binding
brainstorm of *different ways to package* the same candidates so they ride on demand that
already exists, or match how the tracker is organised. This is about acceptance strategy,
not about what the code does.

### Ride the wanted feature — bundle low-demand machinery into a high-demand door

**Block identity & anchors** is the bucket worth packaging — with one refinement.
elemental-base's `Title` (headline), `Name` (CMS label) and `AnchorName` / derived anchor
are one coherent "how is this block identified and addressed" surface: the anchor derives
from the title/name. But upstream demand here is **anchor-shaped, not name-shaped** — open
PR **#1320** explicitly wants *human-readable* block anchors, while a separate CMS-only
`Name` field has no demand at all. So the demand-aligned shape is an **anchor-led** PR: an
editable, human-readable `AnchorName` whose value derives from the block's title (and which
can harvest in-content anchors), landing most of the identity machinery through the door
that is actually open. The `Name`/`Title` *split* then becomes an optional follow-up rather
than the headline — keep the first PR focused on anchors (maintainers favour tight scope)
and float `Name` as a sequel once the anchor surface exists. So: yes, it is a natural
bucket — but lead with anchors, not with Name/Title.

### Split to land the safe part first

**Edit links (A1 + A2).** Instead of one PR, carve out the minimal, hard-to-argue-with fix
— "non-page / ModelAdmin element edit links shouldn't break or resolve the wrong relation"
(directly satisfying #1407 and #778) — and propose the customisation hooks
(`getElementCMSEditLink()` / `updateEvoCMSEditLink`) as a *separate, later* PR. The first is
a bug fix tied to tracked issues; the second is API design that benefits from its own
discussion. Splitting raises the odds the core fix lands quickly.

### Reframe a model change as the bug fix it implies

**Multi-area (A4)** is the exemplar: don't propose the named-area model, propose the fix for
#715 ("can't publish with two areas"). The same move applies anywhere an elemental-base
feature exists *because* it fixed a latent multi-area / non-page bug — extract the bug fix,
leave the model at home.

### Cluster by the user workflow the tracker already groups around

**Elements that handle their own requests (A3 + C4).** #960 (controller has no request),
#965 / #1435 / #1436 (forms-in-elements hang or misbehave) and redirect handling are all
one workflow: an element responding to a request. A focused "element controllers get the
request before init, and can emit a redirect" PR is motivated by that whole cluster of form
bugs — a stronger story than either fix alone, and without needing the area-scoped router
rewrite.

### Align with an in-flight PR instead of competing

Where upstream already has a PR moving the right direction — **#1320** (anchors), **#880**
(field placement), **#1259** (TopPage) — the best "shape" is a review comment plus an
offered enhancement on *their* branch, not a parallel PR. Lowest friction, and it builds the
relationship that makes the larger contributions easier later.

### Where reshaping does *not* help

The Tier-D architecture (named areas, polymorphic parent, current/local inheritance,
area-scoped routing, CMS form restructure) cannot be reshaped into a small wanted PR — there
is no demand wedge and no way to split it without it ceasing to be the thing it is. Its only
realistic venue stays the RFC discussion (#347 / #159).

## Appendix — feature → issue/PR map

| elemental-base feature | Tier | Open issues | Open PRs | Closed (evidence) |
| --- | --- | --- | --- | --- |
| Non-page / ModelAdmin edit links | A1 | #1407, #778, #166, #946 | — | #718, #871 |
| Dynamic area relation name | A2 | (via #715, #531) | — | — |
| Request available in element controller | A3 | #960, #1338 | — | — |
| Multi-area publishing | A4 | #715, #756 | — | — |
| Advanced-edit-while-inline | B1 | #757, #403 | — | — |
| Summary / actions UX | B2 | #582, #1418, #403 | — | — |
| TopPage traversal | B3 | #505, #1415, #1257, #1258, #1021 | #1259 | — |
| Cascade-delete owned areas | B4 | #666, #804 | — | — |
| Block anchors | C1 | #195, #689 | #1320 | — |
| CMS field placement | C2 | #758 | #880 | — |
| Position helper ergonomics | C3 | #1274, #1221 | #1275 | — |
| Forms / redirects in elements | C4 | #965, #1435, #890 | #1436 | — |
| Reusable / provider elements | D | — | — | — |
| Hide-empty / permissions / menus / Name field | D | — | — | — |
| Named-area architecture | D | #347 (RFC), #159 | — | — |
