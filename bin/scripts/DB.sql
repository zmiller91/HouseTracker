create table listings (
	mls_id varchar(25),
    lat varchar(256),
    lng varchar(256),
	city varchar(256),
    state varchar(2),
    zip varchar(5),
    address varchar(256),
    year varchar(4),
    sq_ft varchar(10),
    beds varchar(2),
    baths varchar(2),
    price varchar(10),
    price_per_sqft varchar(256),
    taxes varchar(15),
    floors varchar(256),
    elementary_school varchar(256),
    middle_school varchar(256),
    high_school varchar(256),
    neighborhood varchar(256),
    remarks varchar(256),
    primary_photo varchar(256),
    url varchar(256),
    date_listed datetime,
    primary key (mls_id)
);

create table school_ratings (
    name varchar(256),
    level varchar(50),
    rating varchar(2),
	primary key (name, level)
);
