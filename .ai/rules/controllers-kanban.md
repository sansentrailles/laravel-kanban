---
paths:
  - 'app/Http/Controllers/Kanban/**'
---

# Controllers Kanban

## Card comments are lazy-loaded, not part of the board payload
BoardController does not eager-load comments; cards carry only comments_count. The modal fetches GET cards.comments.index (CommentController@index, CardPolicy@view) when opened, once per card id (commentsLoadedCardIds in resources/js/Stores/board.js). Do not add comments back to the board payload or to CardMoved — a card with many comments can exceed Reverb's ~10 KB limit. Comment realtime events still patch card.comments in place, and selectCard() must keep triggering the lazy fetch.
