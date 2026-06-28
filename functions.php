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

function lay_phong_con_trong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach)
{
    global $loiTaiPhong;

    if ($conn == null) {
        return array();
    }

    $soKhach = (int) $soKhach;
    if ($soKhach < 1) {
        $soKhach = 1;
    }

    if ($ngayNhanPhong != "" && $ngayTraPhong != "") {
        $gioNhanPhong = $ngayNhanPhong . " 14:00:00";
        $gioTraPhong = $ngayTraPhong . " 12:00:00";

        $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh,
                       p.trang_thai, lp.ten_loai_phong, lp.mo_ta, lp.suc_chua,
                       lp.gia_theo_gio, lp.gia_theo_ngay
                FROM phong p
                INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
                WHERE p.trang_thai = 'con_trong'
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
        $stmt->bind_param("iss", $soKhach, $gioTraPhong, $gioNhanPhong);
    } else {
        $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh,
                       p.trang_thai, lp.ten_loai_phong, lp.mo_ta, lp.suc_chua,
                       lp.gia_theo_gio, lp.gia_theo_ngay
                FROM phong p
                INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
                WHERE p.trang_thai = 'con_trong'
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

function ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong)
{
    if ($ngayNhanPhong == "" || $ngayTraPhong == "") {
        return true;
    }

    if (!strtotime($ngayNhanPhong) || !strtotime($ngayTraPhong)) {
        return false;
    }

    return strtotime($ngayTraPhong) > strtotime($ngayNhanPhong);
}

function ten_trang_thai_phong($trangThai)
{
    if ($trangThai == "con_trong") {
        return "Còn trống";
    }

    if ($trangThai == "tu_sua") {
        return "Tu sửa";
    }

    return "Chưa rõ";
}
