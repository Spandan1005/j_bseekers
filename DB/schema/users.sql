create table users (
username varchar(60) not null primary key,
email varchar(255) not null unique,
password char(64) not null,
creation_date timestamp not null default current_timestamp,
last_login timestamp null
);

