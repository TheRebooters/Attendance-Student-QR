<?php
	date_default_timezone_set("Asia/Singapore");
	$response = ''; // Variable to store the response

	if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['time']) && !empty($_GET['time'])) {
		require '../connection.php';
		$uid = $conn->real_escape_string($_GET['id']);
		$time = $conn->real_escape_string(urldecode($_GET['time']));

		$sql = "SELECT * FROM `users` WHERE `uid` = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s", $uid);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$details = $result->fetch_assoc();

			if ($details['access'] === "restricted") {
				$response = '5'; // User restricted
			} elseif ($details['access'] === "regular") {
				$today = date("Y-m-d H:i:s");
						$sql = "INSERT INTO `attendance` VALUES (NULL, ?, 'in', ?)";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param("ss", $uid, $today);

						if ($stmt->execute()) {
							$response = '1'; // Attendance marked in
						} else {
							$response = '0'; // Not marked
						}
			}

			if (empty($response)) {
				$time_difference = time() - strtotime($time);

				if ($time_difference <= 30) {
					$sql = "SELECT * FROM `attendance` WHERE `uid` = ? ORDER BY `aid` DESC LIMIT 1";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param("s", $uid);
					$stmt->execute();
					$result = $stmt->get_result()->fetch_assoc();

					if ($result && date("Y-m-d", strtotime($result['timestamp'])) === date("Y-m-d")) {
						if ($result['action'] === "out") {
							$aid = $result['aid'];
							$current = date("Y-m-d H:i:s");
							$sql = "UPDATE `attendance` SET `timestamp` = ? WHERE `aid` = ?";
							$stmt = $conn->prepare($sql);
							$stmt->bind_param("si", $current, $aid);
							$stmt->execute();

							if ($stmt->affected_rows) {
								$response = '2'; // Attendance updated
							} else {
								$response = '0'; // Not updated
							}
						} else {
							$current = date("Y-m-d H:i:s");
							$sql = "INSERT INTO `attendance` VALUES (NULL, ?, 'out', ?)";
							$stmt = $conn->prepare($sql);
							$stmt->bind_param("ss", $uid, $current);
							if ($stmt->execute()) {
								$response = '1'; // Attendance marked out
							} else {
								$response = '0'; // Not marked
							}
						}
					} else {
						$today = date("Y-m-d H:i:s");
						$sql = "INSERT INTO `attendance` VALUES (NULL, ?, 'in', ?)";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param("ss", $uid, $today);

						if ($stmt->execute()) {
							$response = '1'; // Attendance marked in
						} else {
							$response = '0'; // Not marked
						}
					}
				} else {
					$response = '3'; // QR code expired
				}
			}
		} else {
			$response = '4'; // User does not exist
		}
	} else {
		$response = '7'; // Invalid input
	}

	echo $response; // Echo the final response
?>
