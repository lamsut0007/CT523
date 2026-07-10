<?php
function h($giaTri)
{
    return htmlspecialchars($giaTri, ENT_QUOTES, "UTF-8");
}

function tien_viet_nam($soTien)
{
    return number_format((float) $soTien, 0, ",", ".") . " đồng";
}

function dinh_dang_ngay($ngay)
{
    $mocThoiGian = strtotime($ngay);
    if (!$mocThoiGian) {
        return $ngay;
    }

    return date("d/m/Y", $mocThoiGian);
}

function tao_datetime_tu_ngay_gio($ngay, $gio)
{
    if ($ngay == "" || $gio == "") {
        return "";
    }

    $mocThoiGian = strtotime(trim($ngay) . " " . trim($gio));
    if (!$mocThoiGian) {
        return "";
    }

    return date("Y-m-d H:i:s", $mocThoiGian);
}

function dinh_dang_ngay_gio($ngay, $gio)
{
    $thoiGian = tao_datetime_tu_ngay_gio($ngay, $gio);
    if ($thoiGian == "") {
        return dinh_dang_ngay($ngay);
    }

    return date("d/m/Y H:i", strtotime($thoiGian));
}

function lay_phong_dang_hoat_dong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach, $gioNhanPhong = "14:00", $gioTraPhong = "12:00")
{
    global $loiTaiPhong;

    if ($conn == null) {
        return array();
    }

    $soKhach = (int) $soKhach;
    if ($soKhach < 1) {
        $soKhach = 1;
    }

    $gioNhanPhong = trim($gioNhanPhong);
    $gioTraPhong = trim($gioTraPhong);
    if ($gioNhanPhong == "") {
        $gioNhanPhong = "14:00";
    }
    if ($gioTraPhong == "") {
        $gioTraPhong = "12:00";
    }

    $thoiGianNhanPhong = tao_datetime_tu_ngay_gio($ngayNhanPhong, $gioNhanPhong);
    $thoiGianTraPhong = tao_datetime_tu_ngay_gio($ngayTraPhong, $gioTraPhong);

    if ($ngayNhanPhong != "" && $ngayTraPhong != "" && $thoiGianNhanPhong != "" && $thoiGianTraPhong != "") {

        $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh,
                       p.trang_thai, lp.ten_loai_phong, lp.mo_ta, lp.suc_chua,
                       lp.gia_theo_gio, lp.gia_theo_ngay
                FROM phong p
                INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
                WHERE p.trang_thai = 'dang_hoat_dong'
                  AND lp.suc_chua >= ?
                  AND NOT EXISTS (
                      SELECT 1
                      FROM dat_phong dp
                      WHERE dp.id_phong = p.id_phong
                        AND dp.trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')
                        AND dp.ngay_nhan_phong < ?
                        AND dp.ngay_tra_phong > ?
                  )
                ORDER BY lp.gia_theo_ngay ASC, p.ma_so_phong ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $loiTaiPhong = "Chưa kéo được danh sách phòng. Thử import lại files/db/schema.sql nha, database chắc còn đang dùng bản cũ.";
            return array();
        }
        $stmt->bind_param("iss", $soKhach, $thoiGianTraPhong, $thoiGianNhanPhong);
    } else {
        $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh,
                       p.trang_thai, lp.ten_loai_phong, lp.mo_ta, lp.suc_chua,
                       lp.gia_theo_gio, lp.gia_theo_ngay
                FROM phong p
                INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
                WHERE p.trang_thai = 'dang_hoat_dong'
                  AND lp.suc_chua >= ?
                ORDER BY lp.gia_theo_ngay ASC, p.ma_so_phong ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $loiTaiPhong = "Chưa kéo được danh sách phòng. Thử import lại files/db/schema.sql nha, database chắc còn đang dùng bản cũ.";
            return array();
        }
        $stmt->bind_param("i", $soKhach);
    }

    $stmt->execute();
    $ketQua = $stmt->get_result();
    $danhSachPhong = array();

    while ($dong = $ketQua->fetch_assoc()) {
        $danhSachPhong[] = $dong;
    }

    $stmt->close();
    return $danhSachPhong;
}

function ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong, $gioNhanPhong = "14:00", $gioTraPhong = "12:00")
{
    if ($ngayNhanPhong == "" || $ngayTraPhong == "") {
        return true;
    }

    $thoiGianNhanPhong = tao_datetime_tu_ngay_gio($ngayNhanPhong, $gioNhanPhong);
    $thoiGianTraPhong = tao_datetime_tu_ngay_gio($ngayTraPhong, $gioTraPhong);

    if ($thoiGianNhanPhong == "" || $thoiGianTraPhong == "") {
        return false;
    }

    return strtotime($thoiGianTraPhong) > strtotime($thoiGianNhanPhong);
}

function ten_trang_thai_phong($trangThai)
{
    if ($trangThai == "dang_hoat_dong") {
        return "Đang hoạt động";
    }

    if ($trangThai == "tu_sua") {
        return "Tu sửa";
    }

    return "Chưa rõ";
}
