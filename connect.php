<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

//Connection to Oracle
$conn = oci_connect('sezenwa', '08208364', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(Host=oracle.scs.ryerson.ca)(Port=1521))(CONNECT_DATA=(SID=orcl)))');
if (!$conn) {
    $m = oci_error();
    echo $m['message'];
} else {
    echo "<p style='color: #39FF14; position: fixed; top: 0; right: 0; margin: 10px;'>Successfully connected with Oracle database.</p><br>";
}
//-------------------------------------------------------------------//
//-------------------------DROPPING TABLES---------------------------//
//-------------------------------------------------------------------//

// List of tables to be dropped
$tablesToDrop = [
    'rents',
    'contains',
    'pays',
    'payed_with',
    'pick_up',
    'has',
    'customer',
    'inventory',
    'equipment',
    'rental',
    'payment',
    'location'
];
// FUNCTION TO DROP ALL TABLES
if (isset($_POST['dropAllTables'])) {
    foreach ($tablesToDrop as $table) {
        $dropTableSQL = "DROP TABLE $table";
        $stmt = oci_parse($conn, $dropTableSQL);
        if (oci_execute($stmt)) {
            echo "Table '$table' deleted successfully <br>";
        } else {
            $error = oci_error($conn);
            echo "Error deleting table '$table': " . $error['message'];
        }
    }
}
//-------------------------------------------------------------------//
//------------------------CREATING TABLES----------------------------//
//-------------------------------------------------------------------//

// List of tables to be created
$tablesToCreate = [
    'customer' => "
        CREATE TABLE customer (
            customer_ID INTEGER PRIMARY KEY,
            customer_name VARCHAR2(30) NOT NULL,
            customer_phone VARCHAR2(12) NOT NULL, 
            customer_email VARCHAR2(50)
        )",

    'equipment' => "
        CREATE TABLE equipment (
            equipment_ID INTEGER PRIMARY KEY,
            equipment_availability VARCHAR2(15),
            equipment_type VARCHAR2(30) NOT NULL,
            equipment_model VARCHAR2(30) NOT NULL,
            equipment_brand VARCHAR2(30),
            rental_price NUMBER NOT NULL
        )",

    'rental' => "
        CREATE TABLE rental (
            rental_ID INTEGER PRIMARY KEY,
            rental_status VARCHAR2(15) NOT NULL,
            rent_start_date DATE NOT NULL,
            rent_end_date DATE NOT NULL,
            returned_date DATE,
            late_fees NUMBER,
            damage_fees NUMBER
        )",

    'payment' => "
        CREATE TABLE payment (
            payment_ID INTEGER PRIMARY KEY,
            payment_status VARCHAR2(15) NOT NULL,
            payment_date DATE NOT NULL,
            payment_amount NUMBER DEFAULT 0
        )",

    'inventory' => "
        CREATE TABLE inventory (
            inventory_ID INTEGER PRIMARY KEY,
            quantity INTEGER,
            equipment_ID INTEGER,
            CONSTRAINT fk_equipment_ID FOREIGN KEY (equipment_ID) REFERENCES equipment(equipment_ID)
            ON DELETE CASCADE
        )",

    'location' => "
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
        )",

    'rents' => "
        CREATE TABLE rents (
            customer_ID INTEGER REFERENCES customer(customer_ID),
            equipment_ID INTEGER,
            CONSTRAINT s_equipment_ID FOREIGN KEY (equipment_ID) REFERENCES equipment(equipment_ID)  ON DELETE CASCADE,
            PRIMARY KEY(customer_ID, equipment_ID)
        )",

    'contains' => "
        CREATE TABLE contains (
            rental_ID INTEGER REFERENCES rental(rental_ID),
            equipment_ID INTEGER,
            CONSTRAINT f_equipment_ID FOREIGN KEY (equipment_ID) REFERENCES equipment(equipment_ID)  ON DELETE CASCADE,
            PRIMARY KEY(rental_ID, equipment_ID)
        )",

    'pays' => "
        CREATE TABLE pays (
            customer_ID INTEGER REFERENCES customer(customer_ID),
            payment_ID INTEGER REFERENCES payment(payment_ID),
            PRIMARY KEY(customer_ID, payment_ID)
        )",

    'payed_with' => "
        CREATE TABLE payed_with (
            rental_ID INTEGER REFERENCES rental(rental_ID),
            payment_ID INTEGER REFERENCES payment(payment_ID),
            PRIMARY KEY(rental_ID, payment_ID)
        )",

    'pick_up' => "
        CREATE TABLE pick_up (
            rental_ID INTEGER REFERENCES rental(rental_ID),
            location_ID INTEGER REFERENCES location(location_ID),
            PRIMARY KEY(rental_ID, location_ID)
        )",

    'has' => "
        CREATE TABLE has (
            inventory_ID INTEGER REFERENCES inventory(inventory_ID),
            equipment_ID INTEGER,
            CONSTRAINT k_equipment_ID FOREIGN KEY (equipment_ID) REFERENCES equipment(equipment_ID)  ON DELETE CASCADE,
            PRIMARY KEY(inventory_ID, equipment_ID)
        )",
];

// FUNCTION TO CREATE ALL TABLES
if (isset($_POST['createAllTables'])) {
    foreach ($tablesToCreate as $tableName => $createSQL) {
        $stmt = oci_parse($conn, $createSQL);
        if (oci_execute($stmt)) {
            echo "Table '$tableName' created successfully <br>";
        } else {
            $error = oci_error($conn);
            echo "Error creating table '$tableName': " . $error['message'];
        }
    }
}
//-------------------------------------------------------------------//
//-----------------------POPULATE TABLES-----------------------------//
//-------------------------------------------------------------------//
//Data to insert into tables
function populateTables($conn)
{

    $insertStatements = [
        "INSERT INTO customer VALUES (1234,'Karen','416-123-4567','karen.yeh@torontomu.ca')",
        "INSERT INTO customer VALUES (5678,'Chanuth','416-123-4568','chanuth.pathirana@torontomu.ca')",
        "INSERT INTO customer VALUES (9012,'Sommie','416-123-4569','sezenwa@torontomu.ca')",

        "INSERT INTO equipment VALUES (123,'available','camera','C1234','Sony',100.52)",
        "INSERT INTO equipment VALUES (456,'not available','camera','C5678','Canon',370.05)",
        "INSERT INTO equipment VALUES (789,'available','softbox light','S1234','Fovitec',127.99)",
        "INSERT INTO equipment VALUES (012,'available','camera','C9012','Sony',314.12)",

        "INSERT INTO inventory VALUES (123456,5,123)",
        "INSERT INTO inventory VALUES (789012,0,456)",
        "INSERT INTO inventory VALUES (345678,38,789)",
        "INSERT INTO inventory VALUES (901234,21,012)",

        "INSERT INTO location VALUES (12,'123-456-7890','store12@gmail.com','12 store st.','Toronto','ON','M5E1R4','Canada','9:00-17:00')",
        "INSERT INTO location VALUES (34,'123-456-7891','store34@gmail.com','34 store st.','Toronto','ON','M4E1R0','Canada','10:00-19:00')",

        "INSERT INTO payment VALUES (123456789, 'payed', TO_DATE('2023/09/29', 'YYYY/MM/DD'), 100.52)",
        "INSERT INTO payment VALUES (987654321, 'not payed', TO_DATE('2023/10/05', 'YYYY/MM/DD'), 0)",

        "INSERT INTO rental VALUES (0001, 'completed', TO_DATE('2023/08/29', 'YYYY/MM/DD'), TO_DATE('2023/09/29', 'YYYY/MM/DD'), TO_DATE('2023/09/29', 'YYYY/MM/DD'), 20.00, 51.10)",
        "INSERT INTO rental VALUES (0002, 'in progress', TO_DATE('2023/08/29', 'YYYY/MM/DD'), TO_DATE('2023/09/30', 'YYYY/MM/DD'), NULL, 0, 0)",
        "INSERT INTO rental VALUES (0003, 'in progress', TO_DATE('2023/09/01', 'YYYY/MM/DD'), TO_DATE('2023/10/26', 'YYYY/MM/DD'), NULL, 0, 0)",

        "INSERT INTO rents VALUES (1234,123)",
        "INSERT INTO rents VALUES (5678,456)",

        "INSERT INTO pays VALUES (1234,123456789)",
        "INSERT INTO pays VALUES (5678,987654321)",

        "INSERT INTO payed_with VALUES (0001,123456789)",
        "INSERT INTO payed_with VALUES (0002,987654321)",

        "INSERT INTO pick_up VALUES (0001,12)",
        "INSERT INTO pick_up VALUES (0002,34)",

        "INSERT INTO contains VALUES (0001,123)",
        "INSERT INTO contains VALUES (0002,456)",
        "INSERT INTO contains VALUES (0003,789)",

        "INSERT INTO has VALUES (123456,123)",
        "INSERT INTO has VALUES (789012,456)"
    ];
    //lets you know that tables were created successfully
    foreach ($insertStatements as $insertSQL) {
        $stmt = oci_parse($conn, $insertSQL);
        if (oci_execute($stmt)) {
            echo "Data inserted successfully '$insertSQL'<br>";
        } else {
            $error = oci_error($conn);
            echo "Error inserting data: '$insertSQL'" . $error['message'];
        }
    }
}

// BUTTON TO POPULATE THE TABLES
if (isset($_POST['populateAllTables'])) {
    populateTables($conn);
}

//-------------------------------------------------------------------//
//---------------------------VIEW TABLES-----------------------------//
//-------------------------------------------------------------------//


// Fetch the available tables dynamically depending on which tables is selected
$query = "SELECT table_name FROM all_tables WHERE owner = 'YOUR_SCHEMA_NAME'";
$stid = oci_parse($conn, $query);
$r = oci_execute($stid);

$tables = [];
while ($row = oci_fetch_assoc($stid)) {
    $tables[] = $row['TABLE_NAME'];
}

//buttons for each table
foreach ($tables as $table) {
    echo '<form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
    echo '<input type="submit" class="styled-button" name="viewTable" value="View ' . $table . '" data-table="' . $table . '" />';
    echo '</form>';
}

// Displays the table by row
if (isset($_POST['viewTable'])) {
    $selectedTable = $_POST['viewTable'];
    $query = "SELECT * FROM $selectedTable";
    $stid = oci_parse($conn, $query);
    $r = oci_execute($stid);

    if ($r) {
        echo "<h3>Table: $selectedTable</h3>";
        echo "<table border='1'>";

        // Fetches the first row separately to avoid skipping
        $firstRow = oci_fetch_assoc($stid);
        if ($firstRow) {
            echo "<tr>";
            foreach ($firstRow as $columnName => $value) {
                echo "<th>{$columnName}</th>";
            }
            echo "</tr>";
            echo "<tr>";
            foreach ($firstRow as $value) {
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        } else {
            echo "No data found in the table.";
        }

        // Displays the rest of the table content
        while ($row = oci_fetch_assoc($stid)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "Error fetching data from the '$selectedTable' table.";
    }
}

//-------------------------------------------------------------------//
//---------------------------ADVANCED QUERIES------------------------//
//-------------------------------------------------------------------//
//List of query statments
$advancedQueries = [
    "SELECT equipment_type, COUNT(*) AS total_rentals
    FROM equipment, contains
    WHERE contains.equipment_ID = equipment.equipment_ID
    GROUP BY equipment_type",

    "SELECT equipment_type, AVG(rental_price) AS average_rental_price
    FROM equipment
    GROUP BY equipment_type
    HAVING AVG(rental_price) < 200",

    "SELECT e.*
    FROM equipment e
    WHERE e.equipment_availability = 'available'
    MINUS
    SELECT e.*
    FROM equipment e, contains c, rental r
    WHERE e.equipment_ID = c.equipment_ID
    AND c.rental_ID = r.rental_ID
    AND r.rental_status = 'in progress'",

    "SELECT equipment_model
    FROM equipment e
    WHERE EXISTS 
    (SELECT *
    FROM inventory i 
    WHERE quantity > 0
    AND e.equipment_ID = i.equipment_ID)
    ORDER BY equipment_model",

    "SELECT customer_ID, customer_name
    FROM customer c
    WHERE EXISTS 
    (SELECT *
    FROM equipment e, rents r 
    WHERE equipment_brand = 'Fovitec'
    AND e.equipment_ID = r.equipment_ID
    AND c.customer_ID = r.customer_ID)
    UNION
    (SELECT customer_ID, customer_name
    FROM customer c
    WHERE EXISTS 
    (SELECT *
    FROM equipment e, rents r 
    WHERE equipment_type = 'camera'
    AND e.equipment_ID = r.equipment_ID
    AND c.customer_ID = r.customer_ID))"
];

//When button for Queries is clicked (Query #1 - Query #5)
if (isset($_POST['runAdvancedQuery'])) {
    $queryIndex = $_POST['runAdvancedQuery'];
    $queryToRun = $advancedQueries[$queryIndex];

    $stid = oci_parse($conn, $queryToRun);
    $r = oci_execute($stid);
    
    //Displays the queries as tables
    if ($r) {
        echo "<h3>Advanced Query Result</h3>";
        echo "<table border='1'>";


        $columnNames = oci_num_fields($stid);
        echo "<tr>";
        for ($i = 1; $i <= $columnNames; $i++) {
            echo "<th>" . oci_field_name($stid, $i) . "</th>";
        }
        echo "</tr>";

        while ($row = oci_fetch_assoc($stid)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "Error executing advanced query.";
    }
}

//-------------------------------------------------------------------//
//---------------------------UPDATE TABLES---------------------------//
//-------------------------------------------------------------------//

//Executes when the update button is selected
if (isset($_POST['updateDatabase'])) {
    $updateStatements = [
        "UPDATE equipment SET rental_price = 200.0 WHERE equipment_ID = 123",
        "INSERT INTO equipment VALUES (345,'available','camera','C3456','Sony',500.0)",
        "DELETE FROM equipment WHERE equipment_ID = 456"
    ];

    //lets the user know that the update is in progress
    foreach ($updateStatements as $updateSQL) {
        $stmt = oci_parse($conn, $updateSQL);
        if (oci_execute($stmt)) {
            echo "<p style='color: white;'> Updating in progress...<br></p>";        } 
        else {
            $error = oci_error($conn);
            echo "Error updating database: '$updateSQL'" . $error['message'];
        }
    }
            echo "<ul style='color: white;'><li>updating rental price to 200 for equipment with ID 123...</li>";
            echo "<li>inserting new entry into equipment...</li>";
            echo "<li>deleting entry from equipment...</li>";
            echo "Equipment table updated!";
            echo "</ul></li>";  

    //Displays the updated equipment table
    $query = "SELECT * FROM equipment";
    $stid = oci_parse($conn, $query);
    $r = oci_execute($stid);

    if ($r) {
        echo "<h3>Updated Equipment Table</h3>";
        echo "<table border='1'>";

        $firstRow = oci_fetch_assoc($stid);
        if ($firstRow) {
            echo "<tr>";
            foreach ($firstRow as $columnName => $value) {
                echo "<th>{$columnName}</th>";
            }
            echo "</tr>";
            echo "<tr>";
            foreach ($firstRow as $value) {
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        } else {
            echo "No data found in the table.";
        }

        while ($row = oci_fetch_assoc($stid)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "Error fetching data from the 'equipment' table.";
    }
}


//-------------------------------------------------------------------//
//--------------------INSERTING SPECIFIC RECORDS---------------------//
//-------------------------------------------------------------------//

//code for inserting a row in customer table
//takes in all the attributes as variables and inserts them as a new record when the button is selected
if (isset($_POST['insertCustomer'])) {
    $customerID = $_POST['customerID'];
    $customerName = $_POST['customerName'];
    $customerPhone = $_POST['customerPhone'];
    $customerEmail = $_POST['customerEmail'];
    
    // Insert into 'customer' table
    $insertCustomerSQL = "INSERT INTO customer VALUES ($customerID, '$customerName', '$customerPhone', '$customerEmail')";
    $stmt = oci_parse($conn, $insertCustomerSQL);

    //Lets user know the status of the execution
    if (oci_execute($stmt)) {
        echo "Row inserted into 'customer' table successfully";
    } else {
        $error = oci_error($conn);
        echo "Error inserting data into 'customer' table: " . $error['message'];
    }
}

//code for inserting a new equipment type
// This code updates the equipment and inventory table
//takes in all the attributes as variables and inserts them as a new record when the button is selected
if (isset($_POST['insertEquipment'])) {
    $equipmentID = $_POST['equipmentID'];
    $availability = $_POST['availability'];
    $equipmentType = $_POST['equipmentType'];
    $equipmentModel = $_POST['equipmentModel'];
    $equipmentBrand = $_POST['equipmentBrand'];
    $rentalPrice = $_POST['rentalPrice'];
    $inventoryID = $_POST['inventoryID'];
    $quantity = $_POST['quantity'];

    // Insert into 'equipment' table
    $insertEquipmentSQL = "INSERT INTO equipment VALUES ($equipmentID, '$availability', '$equipmentType', '$equipmentModel', '$equipmentBrand', $rentalPrice)";
    $stmtEquipment = oci_parse($conn, $insertEquipmentSQL);
    // Insert into 'inventory' table
    $insertInventorySQL = "INSERT INTO inventory VALUES ($inventoryID, $quantity, $equipmentID)";
    $stmtInventory = oci_parse($conn, $insertInventorySQL);

    //Lets user know the status of the execution
    if (oci_execute($stmtEquipment) && oci_execute($stmtInventory)) {
        echo "Row inserted into 'equipment' and 'inventory' tables successfully";
    } else {
        $errorEquipment = oci_error($conn);
        $errorInventory = oci_error($conn);
        echo "Error inserting data: " . $errorEquipment['message'] . " " . $errorInventory['message'];
    }
}

//code for inserting new rentals
//This code updates the rental, rents and contains tables
//takes in all the attributes as variables and inserts them as a new record when the button is selected
if (isset($_POST['insertRental'])) {
    $customerID = $_POST['customerID'];
    $equipmentID = $_POST['equipmentID'];
    $rentalID = $_POST['rentalID'];
    $rentalStatus = $_POST['rentalStatus'];
    $rentStartDate = $_POST['rentStartDate'];
    $rentEndDate = $_POST['rentEndDate'];
    $returnedDate = $_POST['returnedDate'];
    $lateFees = $_POST['lateFees'];
    $damageFees = $_POST['damageFees'];

    // Insert into 'rents' table
    $insertRentsSQL = "INSERT INTO rents VALUES ($customerID, $equipmentID)";
    $stmtRents = oci_parse($conn, $insertRentsSQL);

    // Insert into 'rental' table
    $insertRentalSQL = "INSERT INTO rental VALUES ($rentalID, '$rentalStatus', TO_DATE('$rentStartDate', 'YYYY/MM/DD'), TO_DATE('$rentEndDate', 'YYYY/MM/DD'), TO_DATE('$returnedDate', 'YYYY/MM/DD'), $lateFees, $damageFees)";
    $stmtRental = oci_parse($conn, $insertRentalSQL);

    // Insert into 'contains' table
    $insertContainsSQL = "INSERT INTO contains VALUES ($rentalID, $equipmentID)";
    $stmtContains = oci_parse($conn, $insertContainsSQL);

    //Lets user know the status of the execution
    if (oci_execute($stmtRents) && oci_execute($stmtRental) && oci_execute($stmtContains)) {
        echo "Row inserted into 'rents', 'rental', and 'contains' tables successfully";
    } else {
        $errorRents = oci_error($conn);
        $errorRental = oci_error($conn);
        $errorContains = oci_error($conn);
        echo "Error inserting data: " . $errorRents['message'] . " " . $errorRental['message'] . " " . $errorContains['message'];
    }
}
//exit connection
oci_close($conn);

?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="GUI.css">
    <title>ShutterShare Database Records</title>
    <h1 class="heading" >ShutterShare  
        <h3 style="font-size: 20px; color: white; font-family: 'Times New Roman', Times, serif; padding: 15px;">FOR ALL YOUR CAMERA EQUIPMENT NEEDS!</h3>
    </h1>
</head>
<body>

<div class="section">
    <!-- Form for creating & populating tables-->
    <h2>Create and Insert Database Records</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <input type="submit" class="emergency-button" name="createAllTables" value="Create All Tables" />
    <input type="submit" class="emergency-button" name="populateAllTables" value="Populate All Tables" />
    </form>
    <br>

    <!-- Form for viewing tables-->
    <h2>View Database Records</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <!-- button for viewing each table (using loop)-->
    <?php
    foreach ($tablesToDrop as $table) {
        echo '<input type="submit" class="styled-button" name="viewTable" value="' . $table . '" data-table="' . $table . '" />';
    }
    ?>
    </form>
    <div id="tableContent"></div>
</div>

<!-- Form for dropping all tables-->
<div class="section">
    <h2>Delete Database Records</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirmDropAllTables()">
        <!-- button for dropping tables-->
        <input type="submit" class="emergency-button" name="dropAllTables" value="Drop All Tables" />
    </form>
</div>

<!-- Form for Queries -->
<div class="section">
    <h2>Query Tables</h2>
    <?php
    // Display buttons for each advanced query
        foreach ($advancedQueries as $index => $query) {
            echo '<form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
            echo '<input type="hidden" name="runAdvancedQuery" value="' . $index . '" />';
            echo '<input type="submit" class="styled-button" value="Query ' . ($index + 1) . '" />';
            echo '</form>';
        }
    ?>
    <div id="advancedQueryResult"></div>
</div>


<!-- Form for updating equipment table-->
<div class="section">
    <h2>Update Tables</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="submit" class="styled-button" name="updateDatabase" value="Update Equipment table" />
    </form>

    <!-- Form for inserting new customer records-->
    <h2> Insert New Customer </h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="customerID">Customer ID:</label>
        <input type="text" name="customerID" required><br>

        <label for="customerName">Customer Name:</label>
        <input type="text" name="customerName" required><br>

        <label for="customerPhone">Customer Phone:</label>
        <input type="text" name="customerPhone" required><br>

        <label for="customerEmail">Customer Email:</label>
        <input type="text" name="customerEmail"><br>

        <input type="submit" class="styled-button" name="insertCustomer" value="Insert into Customer" />
    </form>

    <!-- Form for inserting new equipment/inventory records-->
    <h2>Insert New Equipment into Inventory </h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="equipmentID">Equipment ID:</label>
        <input type="text" name="equipmentID" required><br>

        <!-- options for availabilty can only be 'available' or 'not available'-->
        <label for="availability">Availability:</label>
        <select name="availability" required>
            <option value="available">Available</option>
            <option value="not available">Not Available</option>
        </select><br>

        <!-- options for equipment type can only be 'camera' or 'softbox light'-->
        <label for="equipmentType">Equipment Type:</label>
        <select name="equipmentType" required>
            <option value="camera">Camera</option>
            <option value="softbox light">Soft Box Light</option>
        </select><br>

        <label for="equipmentModel">Equipment Model:</label>
        <input type="text" name="equipmentModel" required><br>
        
        <!-- options for brands can only be 'Sony' or 'canon' or 'Fovitec'-->
        <label for="equipmentBrand">Equipment Brand:</label>
        <select name="equipmentBrand" required>
            <option value="Sony">Sony</option>
            <option value="Canon">Canon</option>
            <option value="Fovitec">Fovitec</option>
        </select><br>

        <label for="rentalPrice">Rental Price:</label>
        <input type="text" name="rentalPrice" required><br>

        <label for="inventoryID">Inventory ID:</label>
        <input type="text" name="inventoryID" required><br>

        <label for="quantity">Quantity:</label>
        <input type="text" name="quantity" required><br>

        <input type="submit" class="styled-button" name="insertEquipment" value="Insert New Equipment" />
    </form>


    <!-- Form for inserting new rental record-->
    <h2>Insert New Rental</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

    <!-- options for cutomers can only be customers registered in the system-->
    <label for="customerID">Customer ID:</label>
        <select name="customerID" required>
            <?php
            // gets existing customer IDs from the 'customer' table
            $customerIDsQuery = "SELECT customer_ID FROM customer";
            $customerIDsStmt = oci_parse($conn, $customerIDsQuery);
            oci_execute($customerIDsStmt);

            while ($row = oci_fetch_assoc($customerIDsStmt)) {
                echo '<option value="' . $row['CUSTOMER_ID'] . '">' . $row['CUSTOMER_ID'] . '</option>';
            }
            ?>
        </select><br>
        
        <!-- options for equipment can only be equipment in inventory that has a quantity of 1 or more-->
        <label for="equipmentID">Equipment ID:</label>
        <select name="equipmentID" required>
            <?php
            // get equipment IDs with quantity 1 or more from the 'inventory' table
            $availableEquipmentQuery = "SELECT i.equipment_ID
                                        FROM inventory i
                                        WHERE i.quantity >= 1";
            $availableEquipmentStmt = oci_parse($conn, $availableEquipmentQuery);
            oci_execute($availableEquipmentStmt);

            while ($row = oci_fetch_assoc($availableEquipmentStmt)) {
                echo '<option value="' . $row['EQUIPMENT_ID'] . '">' . $row['EQUIPMENT_ID'] . '</option>';
            }
            ?>
        </select><br>

        <label for="rentalID">Rental ID:</label>
        <input type="text" name="rentalID" required><br>
        
        <!-- options for rental status can only be 'completed' or 'in progress'-->
        <label for="rentalStatus">Rental Status:</label>
        <select name="rentalStatus" required>
            <option value="completed">Completed</option>
            <option value="in progress">In Progress</option>
        </select><br>

        <label for="rentStartDate">Rent Start Date:</label>
        <input type="text" name="rentStartDate" placeholder="YYYY/MM/DD" required><br>

        <label for="rentEndDate">Rent End Date:</label>
        <input type="text" name="rentEndDate" placeholder="YYYY/MM/DD" required><br>

        <label for="returnedDate">Returned Date:</label>
        <input type="text" name="returnedDate" placeholder="YYYY/MM/DD"><br>

        <label for="lateFees">Late Fees:</label>
        <input type="text" name="lateFees"><br>

        <label for="damageFees">Damage Fees:</label>
        <input type="text" name="damageFees"><br>
    
        <input type="submit" class="styled-button" name="insertRental" value="Insert into Rents and Rental" />
    </form>
</div>

<!-- whne the 'drop all tables' button is clicked theres a warning because the button deletes all teh current records-->
<script>
    function confirmDropAllTables() {
        return confirm("Are you sure you want to drop all tables? This action cannot be reversed.");
    }
</script>
 

</body>
</html>