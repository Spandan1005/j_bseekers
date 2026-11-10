create table users (
username varchar(60) not null primary key,
email varchar(255) not null unique,
password char(64) not null,
creation_date bigint unsigned not null default unix_timestamp(),
last_login bigint unsigned null,
opt_in tinyint not null default 0
);

