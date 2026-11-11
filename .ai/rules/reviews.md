---
paths:
  - 'modules/Tracking/resources/views/pages/reviews/**'
---

# Reviews

## My reviews rows use a Review button and open on the reviewer's own row
User decision 2026-11-03: My reviews is an exception to the eye-button rule (pages.md). Each row ends in a text button: "Review" (desk.review_action, primary) while the review waits, "Open" otherwise. Every review link (this page, Desk My reviews and Needs you, ask box, old proposal URLs for a waiting reviewer) is built by App\Support\ProposalStoryLink and goes straight to the Story URL with #my-review / #reviewers, never through proposals.show, because wire:navigate drops the #fragment after a redirect. "Asked by me" reads DeskWork::waitingReviews so it always matches the Waiting on others panel.

## My reviews is three buttons, a search and one list (scenario rebuild 2026-11-09)
The Show dropdown (`all`) is gone. Waiting on me, Finished and Asked by me are buttons that call `setState()` (the `state` URL property; Waiting on me is the default and an unknown or old `all` value reads as it). Waiting and Finished are disjoint (they split the viewer's own rows with `isAwaitingReview`) and Asked by me is the Desk's Waiting on others set; `counts()` follows the search but not the pressed button, so a click lists exactly that many. Search matches the proposal or project name; an empty result offers Reset filters. The Review / Open row buttons above are unchanged.
