CREATE TABLE IF NOT EXISTS auth_tokens (
  token varchar(128) NOT NULL,
  expires timestamp NOT NULL,
  user_id integer NOT NULL
	REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  user_name varchar(128) NOT NULL,
  user_pass varchar(128) NOT NULL
);
CREATE INDEX ix_auth_tokens_user_id ON auth_tokens (user_id);

