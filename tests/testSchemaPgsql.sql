CREATE TABLE "member_info" (
  "no" int NOT NULL,
  "state" int default NULL,
  "date" int NOT NULL default '0',
  "ip" varchar(15) NOT NULL default '',
  "id" varchar(32) default NULL,
  "pass" varchar(32) default NULL,
  "email" text,
  "level" int default NULL,
  "name" varchar(32) default NULL,
  "image" text,
  "jumin1" varchar(6) default NULL,
  "jumin2" varchar(7) default NULL,
  "phone1" text,
  "phone2" text,
  "zipcode" text,
  "address1" text,
  "address2" text,
  "head" int NOT NULL,
  "academy" int NOT NULL,
  PRIMARY KEY  ("no")
);


INSERT INTO "member_info" VALUES (1, 1, 0, '111.111.111.111', 'id1', 'pass1', 'email1@email.com', 1, 'name1', 'image1', '111111', '1111111', 'phone11', 'phone21', 'zipcode1', 'address11', 'address21', 1, 1);
INSERT INTO "member_info" VALUES (2, 2, 0, '222.222.222.222', 'id2', 'pass2', 'email2@email.com', 2, 'name2', 'image2', '222222', '2222222', 'phone12', 'phone22', 'zipcode2', 'address12', 'address22', 2, 2);
INSERT INTO "member_info" VALUES (3, 3, 0, '333.333.333.333', 'id3', 'pass3', 'email3@email.com', 3, 'name3', 'image3', '333333', '3333333', 'phone13', 'phone23', 'zipcode3', 'address13', 'address23', 3, 3);
INSERT INTO "member_info" VALUES (4, 4, 0, '444.444.444.444', 'id4', 'pass4', 'email4@email.com', 4, 'name4', 'image4', '444444', '4444444', 'phone14', 'phone24', 'zipcode4', 'address14', 'address24', 4, 4);
INSERT INTO "member_info" VALUES (5, 5, 0, '555.555.555.555', 'id5', 'pass5', 'email5@email.com', 5, 'name5', 'image5', '555555', '5555555', 'phone15', 'phone25', 'zipcode5', 'address15', 'address25', 5, 5);

CREATE TABLE "student_info" (
  "no" int NOT NULL,
  "state" int NOT NULL,
  "ip" varchar(30) NOT NULL,
  "date" int NOT NULL,
  "member" int NOT NULL,
  "level" int NOT NULL,
  PRIMARY KEY  ("no")
);

INSERT INTO "student_info" VALUES (1, 1, '127.0.0.1', 0, 1, 1);
INSERT INTO "student_info" VALUES (2, 0, '127.0.0.1', 0, 2, 2);
INSERT INTO "student_info" VALUES (3, 0, '127.0.0.1', 0, 3, 3);
INSERT INTO "student_info" VALUES (4, 0, '127.0.0.1', 0, 4, 4);

CREATE TABLE "order_list" (
  "no" int NOT NULL ,
  "ip" varchar(30) NOT NULL default '',
  "state" int NOT NULL default '0',
  "date" int NOT NULL default '0',
  "member_id" int NOT NULL default '0',
  "tot_price" int default NULL,
  "paid_man" varchar(30) NOT NULL default '',
  "etc" text NOT NULL,
  "deliver_state" varchar(4) NOT NULL default 'M',
  "deliver_name" varchar(30) NOT NULL default '',
  "deliver_no" varchar(30) NOT NULL default '',
  "deliver_date" int NOT NULL default '0',
  "crew" int NOT NULL default '0',
  "is_academy" int NOT NULL default '0',
  "head" int NOT NULL default '0',
  "academy" int NOT NULL default '0',
  "is_deliver" int NOT NULL default '0',
  "is_paid" int NOT NULL default '0',
  "pay_method" varchar(8) default 'account',
  "modified_date" int NOT NULL default '0',
  "accept_date" int NOT NULL default '0',
  PRIMARY KEY  ("no")
);

CREATE TABLE "order_product" (
  "no" int NOT NULL ,
  "ip" varchar(30) NOT NULL default '',
  "state" int NOT NULL default '0',
  "date" int NOT NULL default '0',
  "order" int NOT NULL default '0',
  "product" int NOT NULL default '0',
  "ea" int NOT NULL default '0',
  "price" int NOT NULL default '0',
  PRIMARY KEY  ("no")
);

CREATE TABLE "product_list" (
  "no" int NOT NULL ,
  "ip" varchar(30) NOT NULL default '',
  "state" int NOT NULL default '0',
  "date" int NOT NULL default '0',
  "member_id" int NOT NULL default '0',
  "l_category" varchar(30) NOT NULL default '0',
  "m_category" text NOT NULL,
  "r_category" text NOT NULL,
  "name" varchar(50) NOT NULL default '',
  "is_eastern" int NOT NULL default '0',
  "is_teacher" int NOT NULL default '0',
  "price" int NOT NULL default '0',
  "max_ea" int NOT NULL default '0',
  "etc" text NOT NULL,
  "head_price" int NOT NULL default '0',
  "academy_price" int NOT NULL default '0',
  "head" int NOT NULL default '0',
  "precedence" int NOT NULL default '0',
  PRIMARY KEY  ("no")
);

INSERT INTO "order_list" VALUES (1, '', 1, 0, 0, 1, 'paid_man1', 'etc1', 'M', 'deliver_name1', 'deliver_no1', 0, 0, 0, 0, 0, 0, 0, 'account', 0, 0);
INSERT INTO "order_list" VALUES (2, '', 1, 0, 0, 2, 'paid_man2', 'etc2', 'M', 'deliver_name2', 'deliver_no2', 0, 0, 0, 0, 0, 0, 0, 'account', 0, 0);
INSERT INTO "order_list" VALUES (3, '', 0, 0, 0, 3, 'paid_man3', 'etc3', 'M', 'deliver_name3', 'deliver_no3', 0, 0, 0, 0, 0, 0, 0, 'account', 0, 0);
INSERT INTO "order_list" VALUES (4, '', 1, 0, 0, 4, 'paid_man4', 'etc4', 'M', 'deliver_name4', 'deliver_no4', 0, 0, 0, 0, 0, 0, 0, 'account', 0, 0);

INSERT INTO "order_product" VALUES (1, '', 1, 0, 1, 1, 1, 0);
INSERT INTO "order_product" VALUES (2, '', 1, 0, 2, 1, 2, 0);
INSERT INTO "order_product" VALUES (3, '', 1, 0, 2, 2, 2, 0);
INSERT INTO "order_product" VALUES (4, '', 1, 0, 3, 1, 3, 0);
INSERT INTO "order_product" VALUES (5, '', 1, 0, 3, 2, 3, 0);
INSERT INTO "order_product" VALUES (6, '', 1, 0, 3, 3, 3, 0);

INSERT INTO "product_list" VALUES (1, '', 1, 0, 0, 'l_category1', 'm_category1', 'r_category1', 'name1', 0, 0, 0, 0, 'etc1', 0, 0, 0, 0);
INSERT INTO "product_list" VALUES (2, '', 1, 0, 0, 'l_category2', 'm_category2', 'r_category2', 'name2', 0, 0, 0, 0, '', 0, 0, 0, 0);
INSERT INTO "product_list" VALUES (3, '', 1, 0, 0, 'l_category3', 'm_category3', 'r_category3', 'name3', 0, 0, 0, 0, '', 0, 0, 0, 0);