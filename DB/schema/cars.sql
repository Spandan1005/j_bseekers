create table cars (
    id bigint unsigned not null auto_increment primary key,
    username varchar(60) not null,
    year smallint unsigned not null,
    make varchar(50) not null,
    model varchar(50) not null,
    creation bigint unsigned not null default (unix_timestamp()),
    constraint fk_user_cars
    foreign key (username) references users(username)
    on delete cascade on update cascade,
    index idx_cars_user (username)
);
