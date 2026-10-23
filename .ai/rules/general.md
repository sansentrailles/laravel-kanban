---
paths:
  - docker-compose.yml
---

# General

## Restart the queue worker after PHP code changes
ShouldBroadcast events (CardCreated/CardMoved/ColumnUpdated etc.) are sent by the long-lived 'queue' container worker — it computes broadcastWith() and builds the Reverb payload. It does not pick up PHP edits automatically: after changing app code, run 'docker compose restart queue' (and 'app' for FPM opcache) before assuming a fix works in the browser. Symptom of a stale worker: code change verified via tinker/tests, but browser behavior does not change.
