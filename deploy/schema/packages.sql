create table packages (
	version int unsigned not null primary key,
	status enum('new', 'safe', 'bad') not null default 'new',
	file_path varchar(255) not null,
	checksum char(64) not null,
	creation bigint unsigned not null default (unix_timestamp()),
	updated bigint unsigned not null default (unix_timestamp())
);
