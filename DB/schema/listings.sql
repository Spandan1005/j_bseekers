create table listings (
    car_id bigint unsigned not null primary key,
    price decimal(12,2) unsigned not null,
    mileage bigint unsigned not null,
    color varchar(30) not null,
    location varchar(50) not null,
    description text,
    creation bigint unsigned not null default (unix_timestamp()),
    updated bigint unsigned not null,
    constraint fk_listings_car foreign key (car_id) references cars(car_id),
    on update cascade on delete cascade
);
