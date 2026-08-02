<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');


const BOOKING_MIN_LEAD_SECONDS = 3600;

function h($giaTri)
{
    return htmlspecialchars((string) $giaTri, ENT_QUOTES, 'UTF-8');
}

function asset_url($duongDan)
{
    $duongDan = trim((string) $duongDan);
    if ($duongDan === '' || preg_match('#^(https?:)?//#', $duongDan) || strpos($duongDan, 'data:') === 0) {
        return $duongDan;
    }

    $baseDir = isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] !== ''
        ? dirname($_SERVER['SCRIPT_FILENAME']) : dirname(__DIR__);
    $duongDanFile = preg_replace('/\?.*$/', '', str_replace('\\', '/', $duongDan));
    $duongDanThucTe = $duongDanFile;
    if ($duongDanFile !== '' && $duongDanFile[0] !== '/' && !preg_match('#^[A-Za-z]:/#', $duongDanFile)) {
        $duongDanThucTe = $baseDir . '/' . $duongDanFile;
    }
    $duongDanThucTe = realpath(str_replace('//', '/', $duongDanThucTe));
    if (($duongDanThucTe === false || !is_file($duongDanThucTe)) && $duongDanFile !== '' && $duongDanFile[0] !== '/') {
        $duongDanThucTe = realpath(dirname($baseDir) . '/' . $duongDanFile);
    }
    if ($duongDanThucTe === false || !is_file($duongDanThucTe)) return $duongDan;
    $timestamp = filemtime($duongDanThucTe);
    return $timestamp === false ? $duongDan : $duongDan . (strpos($duongDan, '?') === false ? '?' : '&') . 'v=' . $timestamp;
}

function tien_viet_nam($soTien)
{
    return number_format((float) $soTien, 0, ',', '.') . ' đồng';
}

function dinh_dang_ngay($ngay)
{
    $moc = strtotime((string) $ngay);
    return $moc ? date('d/m/Y', $moc) : $ngay;
}

function tao_datetime_tu_ngay_gio($ngay, $gio)
{
    if (trim((string) $ngay) === '' || trim((string) $gio) === '') return '';
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', trim($ngay) . ' ' . trim($gio));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$dt || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) return '';
    return $dt->format('Y-m-d H:i:s');
}

function dinh_dang_ngay_gio($ngay, $gio)
{
    $thoiGian = tao_datetime_tu_ngay_gio($ngay, $gio);
    return $thoiGian ? date('d/m/Y H:i', strtotime($thoiGian)) : dinh_dang_ngay($ngay);
}

function thoi_diem_dat_toi_thieu($soGio = 1)
{
    return (new DateTimeImmutable('now'))->modify('+' . max(1, (int) $soGio) . ' hours');
}

function ngay_gio_min_booking($soGio = 1)
{
    return thoi_diem_dat_toi_thieu($soGio)->format('Y-m-d');
}

function validate_booking_window($ngayNhan, $gioNhan, $ngayTra, $gioTra, $kieuDat = 'ngay', $minLeadSeconds = BOOKING_MIN_LEAD_SECONDS)
{
    $errors = array();
    $nhan = DateTimeImmutable::createFromFormat('!Y-m-d H:i', trim($ngayNhan) . ' ' . trim($gioNhan));
    $tra = DateTimeImmutable::createFromFormat('!Y-m-d H:i', trim($ngayTra) . ' ' . trim($gioTra));
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$nhan || !$tra || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
        return array('ngay_nhan' => 'Ngày hoặc giờ đặt phòng không hợp lệ.');
    }

    $now = new DateTimeImmutable('now');
    if ($nhan->getTimestamp() < $now->getTimestamp() + (int) $minLeadSeconds) {
        $errors['ngay_nhan'] = 'Thời gian nhận phòng phải lớn hơn hiện tại ít nhất 1 giờ.';
    }
    if ($tra <= $nhan) {
        $errors['ngay_tra'] = 'Thời gian trả phòng phải sau thời gian nhận phòng.';
    }
    if ($kieuDat === 'ngay' && $nhan->format('Y-m-d') === $tra->format('Y-m-d')) {
        $errors['ngay_tra'] = 'Đặt theo đêm phải có ít nhất 1 đêm.';
    }
    return $errors;
}

function ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong, $gioNhanPhong = '14:00', $gioTraPhong = '12:00')
{
    if ($ngayNhanPhong === '' || $ngayTraPhong === '') return true;
    return empty(validate_booking_window($ngayNhanPhong, $gioNhanPhong, $ngayTraPhong, $gioTraPhong, 'gio', 0));
}

function ten_trang_thai_phong($trangThai)
{
    return array(
        'dang_hoat_dong' => 'Hoạt động',
        'tu_sua' => 'Đang tu sửa',
        'tam_dung' => 'Tạm dừng',
    )[$trangThai] ?? 'Chưa rõ';
}

function ten_trang_thai_dat($trangThai)
{
    return array(
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'da_nhan_phong' => 'Đã nhận phòng',
        'hoan_thanh' => 'Hoàn thành',
        'da_huy' => 'Đã hủy',
    )[$trangThai] ?? 'Chưa rõ';
}

function dat_phong_dang_giu_cho_sql()
{
    
    return "('da_xac_nhan', 'da_nhan_phong')";
}

function phong_select_sql()
{
    return "SELECT p.id_phong, p.id_loai_phong, p.ma_so_phong, p.ten_phong,
                   p.hinh_dai_dien AS duong_dan_hinh,
                   p.hinh_noi_that_1 AS hinh_2, p.hinh_noi_that_2 AS hinh_3, p.hinh_noi_that_3 AS hinh_4,
                   p.dien_tich, p.so_giuong, p.loai_giuong, p.co_tu_lanh, p.co_nha_bep,
                   p.co_tivi_may_chieu, p.co_bon_tam, p.co_ban_cong,
                   p.co_sofa, p.co_cua_so, p.tang, p.vi_tri, p.huong_view,
                   p.gia_theo_gio AS gia_phong_gio, p.gia_theo_ngay AS gia_phong_ngay,
                   p.trang_thai, lp.ten_loai_phong, lp.mo_ta, lp.suc_chua,
                   lp.gia_theo_gio, lp.gia_theo_ngay
            FROM phong p INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong";
}

function danh_sach_tien_nghi_phong($phong)
{
    $list = array();
    $soGiuong = isset($phong['so_giuong']) ? (int)$phong['so_giuong'] : 1;
    $loaiGiuong = (isset($phong['loai_giuong']) && $phong['loai_giuong'] === 'don') ? 'giường đơn' : 'giường đôi';
    $list[] = array(
        'key' => 'giuong',
        'label' => $soGiuong . ' ' . $loaiGiuong,
        'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16M2 8h20v12M2 17h20M6 8v3M10 8v3"/><path d="M6 11h12"/></svg>'
    );
    if (!empty($phong['co_tu_lanh'])) {
        $list[] = array(
            'key' => 'tu_lanh',
            'label' => 'Tủ lạnh',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="5" y1="10" x2="19" y2="10"/><line x1="9" y1="6" x2="9" y2="8"/><line x1="9" y1="14" x2="9" y2="17"/></svg>'
        );
    }
    if (!empty($phong['co_nha_bep'])) {
        $list[] = array(
            'key' => 'nha_bep',
            'label' => 'Nhà bếp',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.8V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v10.8"/><path d="M3 13.8h18a1 1 0 0 1 1 1V21a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-6.2a1 1 0 0 1 1-1z"/><circle cx="7" cy="18" r="1"/><circle cx="12" cy="18" r="1"/></svg>'
        );
    }
    if (!empty($phong['co_tivi_may_chieu'])) {
        $list[] = array(
            'key' => 'tivi_may_chieu',
            'label' => 'TV / Máy chiếu',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="13" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>'
        );
    }
    
    
    $labelTam = !empty($phong['co_bon_tam']) ? 'Phòng tắm có bồn tắm & nước nóng' : 'Phòng tắm máy nước nóng';
    $list[] = array(
        'key' => 'nha_tam',
        'label' => $labelTam,
        'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h6M12 3v3M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1z"/><path d="M6 12V5a2 2 0 0 1 2-2h3"/><line x1="4" y1="20" x2="5" y2="22"/><line x1="20" y1="20" x2="19" y2="22"/></svg>'
    );

    if (!empty($phong['co_ban_cong'])) {
        $list[] = array(
            'key' => 'ban_cong',
            'label' => 'Ban công',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M3 10h18M4 10v11M8 10v11M12 10v11M16 10v11M20 10v11M3 6h18M5 6V3M19 6V3"/></svg>'
        );
    }
    if (!empty($phong['co_sofa'])) {
        $list[] = array(
            'key' => 'sofa',
            'label' => 'Sofa',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"/><path d="M2 11a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-5z"/><path d="M4 18v2M20 18v2"/></svg>'
        );
    }
    if (!empty($phong['co_cua_so'])) {
        $list[] = array(
            'key' => 'cua_so',
            'label' => 'Cửa sổ',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/><line x1="3" y1="12" x2="21" y2="12"/></svg>'
        );
    }
    return $list;
}

function lay_phong_dang_hoat_dong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach, $gioNhanPhong = '14:00', $gioTraPhong = '12:00')
{
    global $loiTaiPhong;
    if ($conn === null) return array();
    $soKhach = max(1, (int) $soKhach);
    $coKhungGio = $ngayNhanPhong !== '' && $ngayTraPhong !== '' && tao_datetime_tu_ngay_gio($ngayNhanPhong, $gioNhanPhong) !== '' && tao_datetime_tu_ngay_gio($ngayTraPhong, $gioTraPhong) !== '';
    $params = array($soKhach);
    $types = 'i';
    $where = " WHERE p.trang_thai = 'dang_hoat_dong' AND lp.suc_chua >= ? ";
    if ($coKhungGio) {
        $tu = tao_datetime_tu_ngay_gio($ngayNhanPhong, $gioNhanPhong);
        $den = tao_datetime_tu_ngay_gio($ngayTraPhong, $gioTraPhong);
        $where .= " AND NOT EXISTS (SELECT 1 FROM dat_phong dp WHERE dp.id_phong = p.id_phong
                    AND dp.trang_thai IN " . dat_phong_dang_giu_cho_sql() . "
                    AND dp.ngay_nhan_phong < ? AND dp.ngay_tra_phong > ?) ";
        $params[] = $den; $params[] = $tu; $types .= 'ss';
    }
    $stmt = $conn->prepare(phong_select_sql() . $where . ' ORDER BY COALESCE(p.gia_theo_ngay, lp.gia_theo_ngay), p.ma_so_phong');
    if (!$stmt) { $loiTaiPhong = 'Không tải được danh sách phòng. Hãy chạy lại file assets/nhom2.sql hoặc assets/migration_v2.sql.'; return array(); }
    if (count($params) === 1) {
        $stmt->bind_param('i', $params[0]);
    } else {
        $stmt->bind_param('iss', $params[0], $params[1], $params[2]);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $row['gia_theo_ngay'] = $row['gia_phong_ngay'] !== null ? $row['gia_phong_ngay'] : $row['gia_theo_ngay'];
        $row['gia_theo_gio'] = $row['gia_phong_gio'] !== null ? $row['gia_phong_gio'] : $row['gia_theo_gio'];
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function lay_phong_theo_id($conn, $idPhong, $chiLayHoatDong = true)
{
    if ($conn === null || (int) $idPhong <= 0) return null;
    $where = $chiLayHoatDong ? " AND p.trang_thai = 'dang_hoat_dong'" : '';
    $stmt = $conn->prepare(phong_select_sql() . ' WHERE p.id_phong = ?' . $where . ' LIMIT 1');
    if (!$stmt) return null;
    $idPhong = (int) $idPhong;
    $stmt->bind_param('i', $idPhong);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($room) {
        $room['gia_theo_ngay'] = $room['gia_phong_ngay'] !== null ? $room['gia_phong_ngay'] : $room['gia_theo_ngay'];
        $room['gia_theo_gio'] = $room['gia_phong_gio'] !== null ? $room['gia_phong_gio'] : $room['gia_theo_gio'];
    }
    return $room ?: null;
}

function phong_co_the_dat($conn, $idPhong, $tu, $den, $boQuaId = 0)
{
    if ($conn === null || !$tu || !$den || strtotime($den) <= strtotime($tu)) return false;
    $sql = "SELECT COUNT(*) AS total FROM dat_phong WHERE id_phong = ?
            AND trang_thai IN " . dat_phong_dang_giu_cho_sql() . "
            AND ngay_nhan_phong < ? AND ngay_tra_phong > ?";
    if ($boQuaId > 0) $sql .= ' AND id_dat_phong <> ' . (int) $boQuaId;
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $idPhong = (int) $idPhong;
    $stmt->bind_param('iss', $idPhong, $den, $tu);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return (int) $total === 0;
}

function lay_don_trung_lich($conn, $idPhong, $tu, $den, $boQuaId = 0)
{
    if ($conn === null || !$tu || !$den) return null;
    $sql = "SELECT id_dat_phong, ho_ten, ngay_nhan_phong, ngay_tra_phong
            FROM dat_phong
            WHERE id_phong = ?
              AND trang_thai IN " . dat_phong_dang_giu_cho_sql() . "
              AND ngay_nhan_phong < ? AND ngay_tra_phong > ?";
    if ($boQuaId > 0) $sql .= ' AND id_dat_phong <> ' . (int) $boQuaId;
    $sql .= ' ORDER BY ngay_nhan_phong ASC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $idPhong = (int) $idPhong;
    $stmt->bind_param('iss', $idPhong, $den, $tu);
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $conflict ?: null;
}

function lay_lich_dat_phong($conn, $idPhong, $soThang = 3)
{
    if ($conn === null || (int) $idPhong <= 0) return null;
    $soThang = max(1, min(6, (int) $soThang));
    $tuNgay = date('Y-m-d H:i:s');
    $denNgay = date('Y-m-d H:i:s', strtotime('+' . $soThang . ' months'));
    $sql = "SELECT ngay_nhan_phong, ngay_tra_phong, kieu_dat, trang_thai
            FROM dat_phong WHERE id_phong = ?
            AND trang_thai IN " . dat_phong_dang_giu_cho_sql() . "
            AND ngay_tra_phong > ? AND ngay_nhan_phong < ? ORDER BY ngay_nhan_phong";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $idPhong = (int) $idPhong;
    $stmt->bind_param('iss', $idPhong, $tuNgay, $denNgay);
    $stmt->execute();
    $result = $stmt->get_result(); $rows = array();
    while ($row = $result->fetch_assoc()) $rows[] = array('tu' => $row['ngay_nhan_phong'], 'den' => $row['ngay_tra_phong'], 'kieu' => $row['kieu_dat'], 'trang_thai' => $row['trang_thai']);
    $stmt->close();
    return $rows;
}

function goi_y_ngay_trong_cho_phong($conn, $idPhong, $soDem, $tuNgay = null, $soNgayTim = 30)
{
    $tuNgay = $tuNgay ?: date('Y-m-d', strtotime('+1 day'));
    $soDem = max(1, (int) $soDem); $soNgayTim = max($soDem, min(120, (int) $soNgayTim));
    $start = new DateTimeImmutable($tuNgay); $suggestions = array();
    for ($offset = 0; $offset <= $soNgayTim - $soDem && count($suggestions) < 5; $offset++) {
        $checkin = $start->modify('+' . $offset . ' days');
        $checkout = $checkin->modify('+' . $soDem . ' days');
        $tu = $checkin->setTime(14, 0)->format('Y-m-d H:i:s');
        $den = $checkout->setTime(12, 0)->format('Y-m-d H:i:s');
        if (phong_co_the_dat($conn, $idPhong, $tu, $den)) $suggestions[] = array('tu' => $checkin->format('Y-m-d'), 'den' => $checkout->format('Y-m-d'), 'so_dem' => $soDem);
    }
    return $suggestions;
}

function goi_y_chia_phong_lien_tuc($conn, $ngayNhan, $ngayTra, $soKhach)
{
    if ($conn === null) return array();
    $start = new DateTimeImmutable($ngayNhan); $end = new DateTimeImmutable($ngayTra);
    $soDem = (int) $start->diff($end)->days;
    if ($soDem < 2) return array();
    $rooms = lay_phong_dang_hoat_dong($conn, '', '', $soKhach);
    $plan = array(); $currentRoom = 0; $segmentStart = null;
    for ($i = 0; $i < $soDem; $i++) {
        $day = $start->modify('+' . $i . ' days');
        $tu = $day->setTime($i === 0 ? 14 : 12, 0)->format('Y-m-d H:i:s');
        $den = $day->modify('+1 day')->setTime(12, 0)->format('Y-m-d H:i:s');
        $available = array();
        foreach ($rooms as $room) if (phong_co_the_dat($conn, $room['id_phong'], $tu, $den)) $available[] = $room;
        $chosen = null;
        foreach ($available as $room) if ((int) $room['id_phong'] === $currentRoom) { $chosen = $room; break; }
        if (!$chosen && count($available)) $chosen = $available[0];
        if (!$chosen) return array();
        if ($currentRoom !== (int) $chosen['id_phong']) {
            if ($currentRoom > 0) $plan[count($plan) - 1]['den'] = $day->format('Y-m-d');
            $plan[] = array('id_phong' => (int) $chosen['id_phong'], 'ten_phong' => $chosen['ten_phong'], 'ma_so_phong' => $chosen['ma_so_phong'], 'tu' => $day->format('Y-m-d'), 'den' => $day->modify('+1 day')->format('Y-m-d'), 'so_dem' => 1);
            $currentRoom = (int) $chosen['id_phong'];
        } else {
            $plan[count($plan) - 1]['den'] = $day->modify('+1 day')->format('Y-m-d');
            $plan[count($plan) - 1]['so_dem']++;
        }
    }
    return count($plan) > 1 ? $plan : array();
}

function lay_thong_ke_phong($conn, $tuNgay = null, $denNgay = null)
{
    if ($conn === null) return array();
    $tuNgay = $tuNgay ?: date('Y-m-01'); $denNgay = $denNgay ?: date('Y-m-d', strtotime('+1 day'));
    $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.trang_thai, lp.ten_loai_phong,
                   COUNT(dp.id_dat_phong) AS so_luot_dat,
                   COALESCE(SUM(CASE WHEN dp.trang_thai <> 'da_huy' THEN TIMESTAMPDIFF(MINUTE, dp.ngay_nhan_phong, dp.ngay_tra_phong) ELSE 0 END), 0) AS so_phut_su_dung,
                   COALESCE(SUM(CASE WHEN dp.trang_thai <> 'da_huy' THEN 1 ELSE 0 END), 0) AS so_don_hop_le
            FROM phong p INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
            LEFT JOIN dat_phong dp ON dp.id_phong = p.id_phong AND dp.ngay_nhan_phong < ? AND dp.ngay_tra_phong > ?
            GROUP BY p.id_phong ORDER BY so_don_hop_le DESC, so_phut_su_dung DESC, p.ma_so_phong";
    $stmt = $conn->prepare($sql); if (!$stmt) return array();
    $stmt->bind_param('ss', $denNgay, $tuNgay); $stmt->execute(); $res = $stmt->get_result(); $rows = array();
    while ($row = $res->fetch_assoc()) { $row['so_phut_su_dung'] = (int) $row['so_phut_su_dung']; $row['so_luot_dat'] = (int) $row['so_luot_dat']; $rows[] = $row; }
    $stmt->close(); return $rows;
}

function format_duration_minutes($minutes)
{
    $minutes = max(0, (int) $minutes); return floor($minutes / 1440) . ' ngày ' . floor(($minutes % 1440) / 60) . ' giờ';
}
