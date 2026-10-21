create table reviews (
    review_id bigint unsigned not null auto_increment primary key,
    username varchar(60) not null,
    year smallint unsigned not null,
    make varchar(50) not null,
    model varchar(50) not null,
    rating tinyint unsigned not null,
    review text,
    creation bigint unsigned not null default (unix_timestamp()),
    constraint fk_user_reviews
    foreign key (username) references users(username),
    index idx_reviews_user (username)
);
