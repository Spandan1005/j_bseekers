create table complaints (
    complaint_id bigint unsigned not null auto_increment primary key,
    component varchar(50) not null,
    _summary varchar(255) not null,
    year smallint unsigned not null,
    make varchar(50) not null,
    model varchar(50) not null,
    camp_date bigint unsigned not null,
    cache_date bigint unsigned not null default (unix_timestamp())
);
