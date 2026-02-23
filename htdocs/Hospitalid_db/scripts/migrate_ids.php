<?php
require __DIR__ . '/../Includes/database.php';

try {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

    // add numeric id column with auto-increment
    mysqli_query($conn, "ALTER TABLE visitor ADD COLUMN visitor_id_num INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY (visitor_id_num)");

    // add temporary numeric FK columns
    mysqli_query($conn, "ALTER TABLE patient_visit ADD COLUMN valid_id_num INT DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE visit_log ADD COLUMN valid_id_num INT DEFAULT NULL");

    // populate mapping from old varchar ids to new numeric ids
    mysqli_query($conn, "UPDATE patient_visit pv JOIN visitor v ON pv.valid_id = v.visitor_id SET pv.valid_id_num = v.visitor_id_num");
    mysqli_query($conn, "UPDATE visit_log vl JOIN visitor v ON vl.valid_id = v.visitor_id SET vl.valid_id_num = v.visitor_id_num");

    // drop foreign keys that reference visitor.visitor_id
    // constraint names from dump: Visitor_Patient_visit, Visitor_Visit_log
    mysqli_query($conn, "ALTER TABLE patient_visit DROP FOREIGN KEY Visitor_Patient_visit");
    mysqli_query($conn, "ALTER TABLE visit_log DROP FOREIGN KEY Visitor_Visit_log");

    // drop old indexes on those columns
    @mysqli_query($conn, "ALTER TABLE patient_visit DROP INDEX Visitor_Patient_visit");
    @mysqli_query($conn, "ALTER TABLE visit_log DROP INDEX Visitor_Visit_log");

    // replace varchar FK columns with the numeric columns
    mysqli_query($conn, "ALTER TABLE patient_visit DROP COLUMN valid_id");
    mysqli_query($conn, "ALTER TABLE patient_visit CHANGE valid_id_num valid_id INT DEFAULT NULL");

    mysqli_query($conn, "ALTER TABLE visit_log DROP COLUMN valid_id");
    mysqli_query($conn, "ALTER TABLE visit_log CHANGE valid_id_num valid_id INT DEFAULT NULL");

    // remove old visitor_id column and rename numeric column
    mysqli_query($conn, "ALTER TABLE visitor DROP COLUMN visitor_id");
    mysqli_query($conn, "ALTER TABLE visitor CHANGE visitor_id_num visitor_id INT NOT NULL AUTO_INCREMENT");

    // recreate indexes and foreign keys
    mysqli_query($conn, "ALTER TABLE patient_visit ADD INDEX Visitor_Patient_visit (valid_id)");
    mysqli_query($conn, "ALTER TABLE visit_log ADD INDEX Visitor_Visit_log (valid_id)");

    mysqli_query($conn, "ALTER TABLE patient_visit ADD CONSTRAINT Visitor_Patient_visit FOREIGN KEY (valid_id) REFERENCES visitor(visitor_id)");
    mysqli_query($conn, "ALTER TABLE visit_log ADD CONSTRAINT Visitor_Visit_log FOREIGN KEY (valid_id) REFERENCES visitor(visitor_id)");

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

    echo "CONVERT_OK\n";
} catch (Throwable $e) {
    echo 'MIGRATION_ERROR: ' . $e->getMessage() . "\n";
}

?>