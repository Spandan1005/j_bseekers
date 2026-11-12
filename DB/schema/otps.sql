create table otps (
    id bigint unsigned not null auto_increment primary key,
    username varchar(60) not null,
    otp char(6) not null,
    expiration bigint unsigned not null,
    attempts tinyint unsigned not null default 0,
    constraint fk_users_otps
    foreign key (username) references users(username)
    on update cascade on delete cascade,
    index idx_otps_user (username)
);
