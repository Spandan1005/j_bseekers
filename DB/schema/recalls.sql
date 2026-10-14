create table recalls (
    campaign varchar(12) not null primary key,
    component varchar(50) not null,
    _summary varchar(255) not null,
    camp_date bigint unsigned not null,
    cache_date bigint unsigned not null default (unix_timestamp())
);
