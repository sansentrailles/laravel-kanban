---
paths:
  - 'app/Events/Kanban/**'
---

# Kanban

## Comment/checklist changes use dedicated minimal-payload events
Comment create/update/delete broadcast CommentCreated/CommentUpdated/CommentDeleted, checklist changes broadcast ChecklistUpdated (payload keys: card_id, checklists[], checklists_count). Do not reuse CardUpdated for these: a card with many comments/checklists can exceed Reverb's ~10 KB max_message_size and the event is silently dropped. Frontend patches card.comments / card.checklists in resources/js/Stores/board.js.
