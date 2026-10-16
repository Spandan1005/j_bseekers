create table recalls (
    recall_id bigint unsigned not null auto_increment primary key,
    campaign varchar(12) not null,
    component varchar(255) not null,
    _summary text not null,
    year smallint unsigned not null,
    make varchar(50) not null,
    model varchar(50) not null,
    camp_date bigint unsigned not null,
    cache_date bigint unsigned not null default (unix_timestamp())
);
