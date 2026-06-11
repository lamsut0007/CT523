<?php
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function money_vnd($value)
{
    return number_format((float) $value, 0, ",", ".") . " đồng";
}

function format_date_vi($value)
{
    $time = strtotime($value);
    if (!$time) {
        return $value;
    }

    return date("d/m/Y", $time);
}

function get_available_rooms($conn, $checkIn, $checkOut, $guests)
{
    global $roomQueryError;

    if ($conn == null) {
        return array();
    }

    $guests = (int) $guests;
    if ($guests < 1) {
        $guests = 1;
    }

    if ($checkIn != "" && $checkOut != "") {
        $checkInTime = $checkIn . " 14:00:00";
        $checkOutTime = $checkOut . " 12:00:00";

        $sql = "SELECT r.id, r.room_code, r.room_name, r.image_path, rt.name AS type_name,
                       rt.description, rt.capacity, rt.price_per_hour, rt.price_per_day
                FROM rooms r
                INNER JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.status = 'available'
                  AND rt.capacity >= ?
                  AND NOT EXISTS (
                      SELECT 1
                      FROM bookings b
                      WHERE b.room_id = r.id
                        AND b.status IN ('pending', 'confirmed')
                        AND b.check_in < ?
                        AND b.check_out > ?
                  )
                ORDER BY rt.price_per_day ASC, r.room_code ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $roomQueryError = "Không tải được danh sách phòng. Vui lòng import lại files/db/schema.sql để cập nhật dữ liệu.";
            return array();
        }
        $stmt->bind_param("iss", $guests, $checkOutTime, $checkInTime);
    } else {
        $sql = "SELECT r.id, r.room_code, r.room_name, r.image_path, rt.name AS type_name,
                       rt.description, rt.capacity, rt.price_per_hour, rt.price_per_day
                FROM rooms r
                INNER JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.status = 'available'
                  AND rt.capacity >= ?
                ORDER BY rt.price_per_day ASC, r.room_code ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $roomQueryError = "Không tải được danh sách phòng. Vui lòng import lại files/db/schema.sql để cập nhật dữ liệu.";
            return array();
        }
        $stmt->bind_param("i", $guests);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = array();

    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    $stmt->close();
    return $rooms;
}

function is_valid_search_date($checkIn, $checkOut)
{
    if ($checkIn == "" || $checkOut == "") {
        return true;
    }

    if (!strtotime($checkIn) || !strtotime($checkOut)) {
        return false;
    }

    return strtotime($checkOut) > strtotime($checkIn);
}
