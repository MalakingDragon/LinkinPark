<?php
function register($pdo){
    $required_fields = ['first_name', 'last_name', 'plate', 'phone', 'pin'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // Validate phone number
    if (!empty($_POST['phone'])) {
        if (!ctype_digit($_POST['phone'])) {
            $errors['phone'] = 'Phone must contain only numbers';
        } elseif (strlen($_POST['phone']) !== 11) {
            $errors['phone'] = 'Phone must be exactly 11 digits';
        }
    }

    // Validate PIN
    if (!empty($_POST['pin'])) {
        if (!ctype_digit($_POST['pin'])) {
            $errors['pin'] = 'PIN must contain only numbers';
        } elseif (strlen($_POST['pin']) !== 4) {
            $errors['pin'] = 'PIN must be exactly 4 digits';
        }
    }

    // Check for duplicate plate number
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM users WHERE plate = ?");
    $stmt->execute([$_POST['plate']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        $errors['plate'] = 'Plate number already exists';
    }

    if (!empty($errors)) {
        $response = ['success' => false, 'errors' => $errors];
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, plate, phone, pin, user_type) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['plate'], $_POST['phone'], $_POST['pin']]);
        $response = ['success' => true, 'message' => 'User inserted into database'];
    }

    echo json_encode($response);
}


function login($pdo){
    $required_fields = ['plate', 'pin'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }

    // Validate PIN format
    if (!empty($_POST['pin'])) {
        if (!ctype_digit($_POST['pin'])) {
            $errors['pin'] = 'PIN must contain only numbers';
        } elseif (strlen($_POST['pin']) !== 4) {
            $errors['pin'] = 'PIN must be exactly 4 digits';
        }
    }

    if (empty($errors)) {
        $plate = $_POST['plate'];
        $pin = $_POST['pin'];

        // Validate plate and pin format if needed
        // Example: Check if plate number exists and is associated with the correct pin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE plate = ?");
        $stmt->execute([$plate]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $pin == $user['pin']) {
            if ($user['user_type'] === 'admin') {
                $response = [
                    'success' => true,
                    'user_type' => $user['user_type'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'plate' => $user['plate']
                ];
                echo json_encode($response);
                die();
            } else if ($user['user_type'] === 'user') {
                $response = [
                    'success' => true,
                    'user_type' => $user['user_type'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'plate' => $user['plate']
                ];
                echo json_encode($response);
                die();
            } else {
                $response = ['success' => true, 'message' => 'Login successful'];
            }
        } else {
            $response = ['success' => false, 'errors' => ['login' => 'Invalid plate number or PIN']];
        }
    } else {
        $response = ['success' => false, 'errors' => $errors];
    }

    echo json_encode($response);
}

function entrance($pdo) {
    // Set the default timezone to Philippine time
    date_default_timezone_set('Asia/Manila');
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $plate = $_POST["plate"];
        $slot_no = $_POST["slot_no"];

        // Get current timestamp
        $time_in = date("Y-m-d H:i:s");

        // Prepare SQL statement to insert plate number and timestamp into the parkings table
        $sql = "INSERT INTO parkings (plate, slot_no, time_in) VALUES (:plate, :slot_no, :time_in)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':plate', $plate);
        $stmt->bindParam(':slot_no', $slot_no);
        $stmt->bindParam(':time_in', $time_in);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Plate number, slot number inserted successfully!'
            ];
            echo json_encode($response);
            die();
        } else {
            $response = [
                'success' => false,
                'message' => 'Unable to insert plate number, slot number'
            ];
            echo json_encode($response);
            die();
        }
    }
}

function exit_parking($pdo) {
    // Check if the request method is POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve plate number from POST data
        $plate = $_POST["plate"];

        // Check if the plate number exists in the parkings table and hasn't exited yet
        $stmt = $pdo->prepare("SELECT * FROM parkings WHERE plate = :plate AND (time_out IS NULL OR time_out = '')");
        $stmt->bindParam(':plate', $plate);
        $stmt->execute();
        $parking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($parking) {
            $response = [
                'success' => false,
                'message' => 'Car is not currently timed out'
            ];
            echo json_encode($response);
            die();
        } else {
            // Check if the plate number exists in the parkings table
            $stmt = $pdo->prepare("SELECT * FROM parkings WHERE plate = :plate ORDER BY time_in DESC");
            $stmt->bindParam(':plate', $plate);
            $stmt->execute();
            $parking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($parking) {
                // Calculate parking duration
                $time_in = new DateTime($parking['time_in']);
                $time_out = new DateTime($parking['time_out']);
                $duration = $time_out->diff($time_in);
                $minutes = $duration->days * 24 * 60 + $duration->h * 60 + $duration->i; // Total minutes parked

                // Calculate amount to pay
                $amount_to_pay = ceil($minutes / 10) * 5; // 5 pesos per 10 minutes

                $response = [
                    'success' => true,
                    'message' => 'Plate number found!',
                    'amount_to_pay' => $amount_to_pay
                ];
                echo json_encode($response);
                die();
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No active parking found for the provided plate number!'
                ];
                echo json_encode($response);
                die();
            }
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid request method. Please use POST method.'
        ];
        echo json_encode($response);
        die();
    }
}

function latest_in($pdo) {
    // Prepare the SQL statement to retrieve the latest parked car in each slot
    $stmt = $pdo->prepare("SELECT p1.* FROM parkings p1 JOIN (SELECT slot_no, MAX(time_in) AS max_time FROM parkings GROUP BY slot_no) p2 ON p1.slot_no = p2.slot_no AND p1.time_in = p2.max_time");
    $stmt->execute();
    $latest_parked_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($latest_parked_cars) {
        // Format the response
        $response = [
            'success' => true,
            'message' => 'Latest parked cars retrieved successfully',
            'parkings' => []
        ];

        // Add each latest parked car to the response
        foreach ($latest_parked_cars as $parking) {
            $response['parkings'][] = [
                'plate_number' => $parking['plate'],
                'slot_number' => $parking['slot_no']
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'No cars currently parked'
        ];
    }

    // Return the response
    echo json_encode($response);
}



?>