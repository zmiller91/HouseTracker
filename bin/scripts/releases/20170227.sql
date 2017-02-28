
use houses;

alter table listings
add column type varchar(246) default 'Unknown'
after floors,
add column modified_date datetime
after date_listed;