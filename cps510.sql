DROP TABLE rents;
DROP TABLE contains;
DROP TABLE pays;
DROP TABLE payed_with;
DROP TABLE pick_up;
DROP TABLE has;
DROP TABLE customer;
DROP TABLE inventory;
DROP TABLE equipment;
DROP TABLE rental;
DROP TABLE payment;
DROP TABLE location;

/* Creating tables */

CREATE TABLE customer (
customer_ID INTEGER PRIMARY KEY,
customer_name VARCHAR2(30) NOT NULL,
customer_phone VARCHAR2(12) NOT NULL, 
customer_email VARCHAR2(50)
);

CREATE TABLE equipment (
equipment_ID INTEGER PRIMARY KEY,
equipment_availability VARCHAR2(15),
equipment_type VARCHAR2(30) NOT NULL,
equipment_model VARCHAR2(30) NOT NULL,
equipment_brand VARCHAR2(30),
rental_price NUMBER NOT NULL
);

CREATE TABLE rental (
rental_ID INTEGER PRIMARY KEY,
rental_status VARCHAR2(15) NOT NULL,
rent_start_date DATE NOT NULL,
rent_end_date DATE NOT NULL,
returned_date DATE,
late_fees NUMBER,
damage_fees NUMBER
);

CREATE TABLE payment (
payment_ID INTEGER PRIMARY KEY,
payment_status VARCHAR2(15) NOT NULL,
payment_date DATE NOT NULL,
payment_amount NUMBER DEFAULT 0
);

CREATE TABLE inventory (
inventory_ID INTEGER PRIMARY KEY,
quantity INTEGER,
equipment_ID INTEGER,
CONSTRAINT fk_equipment_ID FOREIGN KEY (equipment_ID) REFERENCES equipment(equipment_ID)
);

CREATE TABLE location (
location_ID INTEGER PRIMARY KEY,
location_phone VARCHAR2(12), 
location_email VARCHAR2(50),
street_address VARCHAR2(50) NOT NULL,
city VARCHAR2(30) NOT NULL,
province VARCHAR2(2) NOT NULL,
postal_code VARCHAR2(7) NOT NULL,
country VARCHAR2(30) DEFAULT 'Canada',
location_hours VARCHAR2(15)
);

CREATE TABLE rents (
customer_ID INTEGER REFERENCES customer(customer_ID),
equipment_ID INTEGER REFERENCES equipment(equipment_ID),
PRIMARY KEY(customer_ID,equipment_ID)
);

CREATE TABLE contains (
rental_ID INTEGER REFERENCES rental(rental_ID),
equipment_ID INTEGER REFERENCES equipment(equipment_ID),
PRIMARY KEY(rental_ID,equipment_ID)
);

CREATE TABLE pays (
customer_ID INTEGER REFERENCES customer(customer_ID),
payment_ID INTEGER REFERENCES payment(payment_ID),
PRIMARY KEY(customer_ID,payment_ID)
);

CREATE TABLE payed_with (
rental_ID INTEGER REFERENCES rental(rental_ID),
payment_ID INTEGER REFERENCES payment(payment_ID),
PRIMARY KEY(rental_ID,payment_ID)
);

CREATE TABLE pick_up (
rental_ID INTEGER REFERENCES rental(rental_ID),
location_ID INTEGER REFERENCES location(location_ID),
PRIMARY KEY(rental_ID,location_ID)
);

CREATE TABLE has (
inventory_ID INTEGER REFERENCES inventory(inventory_ID),
equipment_ID INTEGER REFERENCES equipment(equipment_ID),
PRIMARY KEY(inventory_ID,equipment_ID)
);

/* Inserting values into tables */
INSERT INTO customer
VALUES (1234,'Karen','416-123-4567','karen.yeh@torontomu.ca');

INSERT INTO customer
VALUES (5678,'Chanuth','416-123-4568','chanuth.pathirana@torontomu.ca');

INSERT INTO customer
VALUES (9012,'Sommie','416-123-4569','sezenwa@torontomu.ca');

INSERT INTO equipment
VALUES (123,'available','camera','C1234','Sony',100.52);

INSERT INTO equipment
VALUES (456,'not available','camera','C5678','Canon',370.05);

INSERT INTO equipment
VALUES (789,'available','softbox light','S1234','Fovitec',127.99);

INSERT INTO equipment
VALUES (012,'available','camera','C9012','Sony',314.12);

INSERT INTO inventory
VALUES (123456,5,123);

INSERT INTO inventory
VALUES (789012,0,456);

INSERT INTO inventory
VALUES (345678,38,789);

INSERT INTO inventory
VALUES (901234,21,012);

INSERT INTO location
VALUES (12,'123-456-7890','store12@gmail.com','12 store st.','Toronto','ON','M5E1R4','Canada','9:00-17:00');

INSERT INTO location
VALUES (34,'123-456-7891','store34@gmail.com','34 store st.','Toronto','ON','M4E1R0','Canada','10:00-19:00');

INSERT INTO payment
VALUES (123456789,'payed','2023/09/29',100.52);

INSERT INTO payment
VALUES (987654321,'not payed','2023/10/05',0);

INSERT INTO rental
VALUES (0001,'completed','2023/08/29','2023/09/29','2023/09/29',0,0);

INSERT INTO rental
VALUES (0002,'in progress','2023/08/29','2023/09/30','',0,0);

INSERT INTO rents
VALUES (1234,123);

INSERT INTO rents
VALUES (5678,456);

INSERT INTO pays
VALUES (1234,123456789);

INSERT INTO pays
VALUES (5678,987654321);

INSERT INTO payed_with
VALUES (0001,123456789);

INSERT INTO payed_with
VALUES (0002,987654321);

INSERT INTO pick_up
VALUES (0001,12);

INSERT INTO pick_up
VALUES (0002,34);

INSERT INTO contains
VALUES (0001,123);

INSERT INTO contains
VALUES (0002,456);

INSERT INTO has
VALUES (123456,123);

INSERT INTO has
VALUES (789012,456);


/* Queries */

/* List all attributes of each table */
SELECT * FROM customer;
SELECT * FROM equipment;
SELECT * FROM inventory;
SELECT * FROM rental;
SELECT * FROM payment;
SELECT * FROM location;
SELECT * FROM rents;
SELECT * FROM contains;
SELECT * FROM pays;
SELECT * FROM payed_with;
SELECT * FROM pick_up;
SELECT * FROM has;

/* lists only customer ID and name of customer with specific ID */
SELECT customer_ID,customer_name
FROM customer
WHERE customer_ID=1234;

/* lists equipment model and price of all equipment */
SELECT equipment_model,'rental price is: ',rental_price
FROM equipment;

/* lists location ID and hours of all locations */
SELECT location_ID,'store hours: ',location_hours
FROM location;

/* lists all attributes of inventory with quantity>0
   and order by ascending order */
SELECT *
FROM inventory
WHERE quantity > 0
ORDER BY quantity ASC;

/* lists all payments (ID and status only) that are not payed */
SELECT payment_ID,payment_status
FROM payment
WHERE NOT(payment_status='payed');

/* lists all rentals (ID and status only) that are completed */
SELECT rental_ID,rental_status
FROM rental
WHERE rental_status='completed';

/* lists location details and orders by street name */
SELECT location_ID, location_phone, location_email, street_address,city, province, postal_code, country, location_hours
FROM location
ORDER BY street_address;

/* lists all equipment types in inventory without duplicates 
   and orders by equipment type name. 
   Types_of_Equipment is the heading of the output. */
SELECT DISTINCT equipment_type AS Types_of_Equipment
FROM equipment
ORDER BY equipment_type;

/* Lists all rentals that are currently in progress */
SELECT *
FROM rental
WHERE rental_status = 'in progress';


