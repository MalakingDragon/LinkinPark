<?php
{

   function plate_number($pdo){
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $plate_number = $_POST["plate_number"];
    
        // Prepare SQL statement to insert plate number into the parkings table
        $sql = "INSERT INTO parkings (plate_number) VALUES ('$plate_number')";
    
        if ($conn->query($sql) === TRUE) {
            echo "Plate number inserted successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
   }

   function insert_slot_number($pdo) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $slot_number = $_POST["slot_number"];
    
        // Prepare SQL statement to insert slot number into the parkings table
        $sql = "INSERT INTO parkings (slot_number) VALUES (:slot_number)";
    
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':slot_number', $slot_number);
    
        if ($stmt->execute()) {
            echo "Slot number inserted successfully!";
        } else {
            echo "Error: Unable to insert slot number";
        }
    }
}


    echo json_encode($response);
}
?>