---
paths:
  - 'app/Events/Kanban/**'
---

# Kanban

## Comment/checklist changes use dedicated minimal-payload events
Comment create/update/delete broadcast CommentCreated/CommentUpdated/CommentDeleted, checklist changes broadcast ChecklistUpdated (payload keys: card_id, checklists[], checklists_count). Do not reuse CardUpdated for these: a card with many comments/checklists can exceed Reverb's ~10 KB max_message_size and the event is silently dropped. Frontend patches card.comments / card.checklists in resources/js/Stores/board.js.

## Attachment events carry attachment_id and attachments_count
AttachmentUploaded/AttachmentDeleted broadcast card_id and attachments_count (plural, same key as HTTP responses). AttachmentDeleted also sends attachment_id; resources/js/Stores/board.js applyAttachmentChanges filters the card.attachments array by it. Without attachment_id remote clients only decrement the counter and keep the deleted file until reload.

## Card reordering can rebalance ord and broadcasts CardsReordered
MoveCardAction re-reads prev/next under the target column lock and rebalances the column when the computed ord is non-positive or collides with a neighbor (decimal(20,10) precision). A rebalance changes ord for every card in the column, so it broadcasts CardsReordered (column_id, cards[{id, ord}]) WITHOUT toOthers() — the mover needs the new ords too. resources/js/Stores/board.js applyCardsReordered patches card.ord only (relative order is unchanged). CardMoved still announces the moved card itself.
