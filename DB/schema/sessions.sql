create table sessions
(session_key char(64) not null primary key,
username varchar(60) not null,
creation bigint unsigned not null,
expiration bigint unsigned not null,
constraint fk_user_session foreign key (username)
references users(username)
on delete cascade on update cascade,
index idx_sessions_expiration (expiration));
