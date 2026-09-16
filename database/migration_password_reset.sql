USE syscampus;
ALTER TABLE users ADD COLUMN reset_token CHAR(64) NULL;
ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL;
ALTER TABLE users ADD UNIQUE KEY uq_users_reset_token(reset_token);
