create table complaints (
    complaint_id bigint unsigned not null auto_increment primary key,
    component varchar(50) not null,
    _summary text not null,
    year smallint unsigned not null,
    make varchar(50) not null,
    model varchar(50) not null,
    comp_date bigint unsigned not null,
    cache_date bigint unsigned not null default (unix_timestamp())
);
