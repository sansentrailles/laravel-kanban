---
paths:
  - 'app/Http/Resources/**'
---

# Resources

## Frontend payload contract of realtime card events
CardResource must expose card order under the key 'ord' (string decimal:10), not 'order' — the model column is ord, and resources/js/Stores/board.js moveCardRemote reads updatedCard.ord and sorts card.ord. A wrong key yields NaN comparisons: cross-column remote moves always insert the card first and same-column reorders do nothing. Keep the backend key and the frontend reader in sync when renaming.
