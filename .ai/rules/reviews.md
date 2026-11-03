---
paths:
  - 'resources/views/pages/reviews/**'
---

# Reviews

## My reviews rows use a Review button and open on the reviewer's own row
User decision 2026-11-03: My reviews is an exception to the eye-button rule (pages.md). Each row ends in a text button: "Review" (desk.review_action, primary) while the review waits, "Open" otherwise. Every review link (this page, Desk My reviews and Needs you, ask box, old proposal URLs for a waiting reviewer) is built by App\Support\ProposalStoryLink and goes straight to the Story URL with #my-review / #reviewers, never through proposals.show, because wire:navigate drops the #fragment after a redirect. "Asked by me" reads DeskWork::waitingReviews so it always matches the Waiting on others panel.
