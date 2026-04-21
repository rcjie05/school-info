-- ── Concurrent Session Limit Migration ───────────────────────────────────────
-- Adds a session_token column to the users table.
-- Each login writes a new token; any previous session with a different token
-- is automatically invalidated, preventing concurrent logins on the same account.

ALTER TABLE `users`
    ADD COLUMN `session_token` varchar(64) DEFAULT NULL COMMENT 'Active session token — null means no active session',
    ADD COLUMN `session_started_at` datetime DEFAULT NULL COMMENT 'When the current session was started';
