<?php
include 'includes/auth.php'; require_admin_login();
include '../includes/db.php'; include '../includes/functions.php';
$pageTitle = 'Quản lý phòng'; $assetPathPrefix = '../'; $hideBrandText = true; $brandHref = 'index.php';
$headerNavItems = array(array('href'=>'index.php','label'=>'Tổng quan'),array('href'=>'rooms.php','label'=>'Quản lý phòng'),array('href'=>'bookings.php','label'=>'Quản lý đặt phòng'),array('href'=>'logout.php','label'=>'Đăng xuất'));
$message = ''; $error = ''; $showForm = isset($_GET['add']) || isset($_GET['edit_id']); $editId = (int) ($_GET['edit_id'] ?? 0);
$roomTypes = array(); $editing = null;
$values = array(
    'id_loai_phong'=>'', 'ma_so_phong'=>'', 'ten_phong'=>'', 'tang'=>1, 'vi_tri'=>'', 'huong_view'=>'', 'dien_tich'=>'',
    'so_giuong'=>1, 'co_tu_lanh'=>1, 'co_nha_bep'=>0, 'co_tivi_may_chieu'=>1, 'co_nha_tam_bon_hot'=>1,
    'co_ban_cong'=>0, 'co_sofa'=>0, 'co_cua_so'=>1,
    'gia_theo_gio'=>'', 'gia_theo_ngay'=>'', 'trang_thai'=>'dang_hoat_dong'
);

if ($conn) { $res = $conn->query('SELECT id_loai_phong, ten_loai_phong, suc_chua FROM loai_phong ORDER BY ten_loai_phong'); if ($res) while ($row=$res->fetch_assoc()) $roomTypes[]=$row; }

function upload_single_room_image($fileInput, $targetFilenameWithoutExt)
{
    if (!isset($fileInput['name']) || empty($fileInput['name']) || ($fileInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($fileInput['size'] ?? 0) > 5 * 1024 * 1024) return null;
    $mime = function_exists('mime_content_type') ? mime_content_type($fileInput['tmp_name']) : '';
    if (!in_array($mime, array('image/jpeg','image/png','image/webp','image/gif'), true)) return null;
    
    $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    $uploadDir = dirname(__DIR__) . '/assets/furniture/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    
    $finalName = $targetFilenameWithoutExt . '.' . $ext;
    if (move_uploaded_file($fileInput['tmp_name'], $uploadDir . $finalName)) {
        return 'assets/furniture/' . $finalName;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_room') {
        $id = (int) ($_POST['id_phong'] ?? 0);
        $type = (int) ($_POST['id_loai_phong'] ?? 0);
        $code = trim($_POST['ma_so_phong'] ?? '');
        $name = trim($_POST['ten_phong'] ?? '');
        $tang = max(1, (int) ($_POST['tang'] ?? 1));
        $position = trim($_POST['vi_tri'] ?? '');
        $view = trim($_POST['huong_view'] ?? '');
        $area = (float) ($_POST['dien_tich'] ?? 0);
        
        $soGiuong = max(1, (int) ($_POST['so_giuong'] ?? 1));
        $loaiGiuong = in_array($_POST['loai_giuong'] ?? '', array('don', 'doi'), true) ? $_POST['loai_giuong'] : 'doi';
        $coTuLanh = isset($_POST['co_tu_lanh']) ? 1 : 0;
        $coNhaBep = isset($_POST['co_nha_bep']) ? 1 : 0;
        $coTiviMayChieu = isset($_POST['co_tivi_may_chieu']) ? 1 : 0;
        $coBonTam = isset($_POST['co_bon_tam']) ? 1 : 0;
        $coBanCong = isset($_POST['co_ban_cong']) ? 1 : 0;
        $coSofa = isset($_POST['co_sofa']) ? 1 : 0;
        $coCuaSo = isset($_POST['co_cua_so']) ? 1 : 0;

        $priceHour = trim($_POST['gia_theo_gio'] ?? '') === '' ? null : (float) $_POST['gia_theo_gio'];
        $priceDay = trim($_POST['gia_theo_ngay'] ?? '') === '' ? null : (float) $_POST['gia_theo_ngay'];
        $status = in_array($_POST['trang_thai'] ?? '', array('dang_hoat_dong','tu_sua','tam_dung'), true) ? $_POST['trang_thai'] : 'dang_hoat_dong';

        $values = array(
            'id_loai_phong'=>$type, 'ma_so_phong'=>$code, 'ten_phong'=>$name, 'tang'=>$tang, 'vi_tri'=>$position, 'huong_view'=>$view, 'dien_tich'=>$area,
            'so_giuong'=>$soGiuong, 'loai_giuong'=>$loaiGiuong, 'co_tu_lanh'=>$coTuLanh, 'co_nha_bep'=>$coNhaBep, 'co_tivi_may_chieu'=>$coTiviMayChieu,
            'co_bon_tam'=>$coBonTam, 'co_ban_cong'=>$coBanCong, 'co_sofa'=>$coSofa, 'co_cua_so'=>$coCuaSo,
            'gia_theo_gio'=>$priceHour, 'gia_theo_ngay'=>$priceDay, 'trang_thai'=>$status
        );

        if ($type <= 0 || $code === '' || $name === '') {
            $error = 'Vui lòng nhập đủ mã phòng, tên phòng và loại phòng.';
        } else {
            $check = $conn->prepare('SELECT id_phong FROM phong WHERE ma_so_phong=? AND id_phong<>?');
            $check->bind_param('si',$code,$id);
            $check->execute();
            $check->store_result();
            $duplicate=$check->num_rows>0;
            $check->close();
            if ($duplicate) $error='Mã phòng đã tồn tại.';
        }

        if ($error === '') {
            $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '-', $code);

            $uploadedMain  = upload_single_room_image($_FILES['anh_phong_chinh'] ?? array(), $safeCode);
            $uploadedFurnA = upload_single_room_image($_FILES['anh_furniture_a'] ?? array(), $safeCode . '-a');
            $uploadedFurnB = upload_single_room_image($_FILES['anh_furniture_b'] ?? array(), $safeCode . '-b');
            $uploadedFurnC = upload_single_room_image($_FILES['anh_furniture_c'] ?? array(), $safeCode . '-c');

            if ($id > 0) {
                $old = lay_phong_theo_id($conn, $id, false);
                $mainImage = $uploadedMain  ?: $old['duong_dan_hinh'];
                $image2     = $uploadedFurnA ?: ($old['hinh_2'] ?? null);
                $image3     = $uploadedFurnB ?: ($old['hinh_3'] ?? null);
                $image4     = $uploadedFurnC ?: ($old['hinh_4'] ?? null);

                $stmt=$conn->prepare('UPDATE phong SET id_loai_phong=?, ma_so_phong=?, ten_phong=?, tang=?, vi_tri=?, huong_view=?, dien_tich=?, so_giuong=?, loai_giuong=?, co_tu_lanh=?, co_nha_bep=?, co_tivi_may_chieu=?, co_bon_tam=?, co_ban_cong=?, co_sofa=?, co_cua_so=?, hinh_dai_dien=?, hinh_noi_that_1=?, hinh_noi_that_2=?, hinh_noi_that_3=?, gia_theo_gio=?, gia_theo_ngay=?, trang_thai=? WHERE id_phong=?');
                $stmt->bind_param('ississdiiiiiiiiissssddsi', $type, $code, $name, $tang, $position, $view, $area, $soGiuong, $loaiGiuong, $coTuLanh, $coNhaBep, $coTiviMayChieu, $coBonTam, $coBanCong, $coSofa, $coCuaSo, $mainImage, $image2, $image3, $image4, $priceHour, $priceDay, $status, $id);
                if ($stmt->execute()) $message='Đã cập nhật thông tin phòng.'; else $error='Không cập nhật được phòng. Hãy kiểm tra database: ' . $stmt->error;
                $stmt->close();
            } else {
                $mainImage = $uploadedMain  ?: 'assets/room/' . $safeCode . '.jpg';
                $image2     = $uploadedFurnA ?: ('assets/furniture/' . $safeCode . '-a.jpg');
                $image3     = $uploadedFurnB ?: ('assets/furniture/' . $safeCode . '-b.jpg');
                $image4     = $uploadedFurnC ?: ('assets/furniture/' . $safeCode . '-c.jpg');

                $stmt=$conn->prepare('INSERT INTO phong (id_loai_phong,ma_so_phong,ten_phong,tang,vi_tri,huong_view,dien_tich,so_giuong,loai_giuong,co_tu_lanh,co_nha_bep,co_tivi_may_chieu,co_bon_tam,co_ban_cong,co_sofa,co_cua_so,hinh_dai_dien,hinh_noi_that_1,hinh_noi_that_2,hinh_noi_that_3,gia_theo_gio,gia_theo_ngay,trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ississdiiiiiiiiissssdds', $type, $code, $name, $tang, $position, $view, $area, $soGiuong, $loaiGiuong, $coTuLanh, $coNhaBep, $coTiviMayChieu, $coBonTam, $coBanCong, $coSofa, $coCuaSo, $mainImage, $image2, $image3, $image4, $priceHour, $priceDay, $status);
                if ($stmt->execute()) $message='Đã thêm phòng mới.'; else $error='Không thêm được phòng: ' . $stmt->error;
                $stmt->close();
            }
            if ($message !== '') { header('Location: rooms.php?msg=' . urlencode($message)); exit; }
        }
    } elseif ($action === 'delete_room') {
        $id=(int)($_POST['id_phong']??0);
        $check=$conn->prepare("SELECT COUNT(*) FROM dat_phong WHERE id_phong=? AND trang_thai IN ('da_xac_nhan','da_nhan_phong') AND ngay_tra_phong>NOW()");
        $check->bind_param('i',$id); $check->execute(); $check->bind_result($count); $check->fetch(); $check->close();
        if ($count>0) $error='Phòng còn lịch đặt trong tương lai nên không thể xóa. Hãy chuyển sang Tạm dừng.';
        else {
            $stmt=$conn->prepare('DELETE FROM phong WHERE id_phong=?');
            $stmt->bind_param('i',$id);
            $message=$stmt->execute()?'Đã xóa phòng.':'Không thể xóa phòng đã có lịch sử đặt.';
            $stmt->close();
        }
        if ($error==='' && $message!=='') { header('Location: rooms.php?msg='.urlencode($message)); exit; }
    }
}

if (isset($_GET['msg'])) $message=$_GET['msg'];
if ($editId > 0 && $conn) {
    $editing=lay_phong_theo_id($conn,$editId,false);
    if ($editing) {
        $showForm=true;
        foreach ($values as $key=>$unused) if (array_key_exists($key,$editing)) $values[$key]=$editing[$key];
        $values['gia_theo_gio']=$editing['gia_phong_gio'];
        $values['gia_theo_ngay']=$editing['gia_phong_ngay'];
    }
}

$rooms=array();
if ($conn) {
    $res=$conn->query("SELECT p.*,lp.ten_loai_phong,lp.suc_chua,(SELECT COUNT(*) FROM dat_phong d WHERE d.id_phong=p.id_phong AND d.trang_thai IN ('da_xac_nhan','da_nhan_phong') AND d.ngay_tra_phong>NOW()) AS future_bookings FROM phong p INNER JOIN loai_phong lp ON lp.id_loai_phong=p.id_loai_phong ORDER BY p.tang,p.ma_so_phong");
    if ($res) while($row=$res->fetch_assoc()) $rooms[]=$row;
}
include '../includes/header.php';
?>
<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <div>
                <h1>Quản lý phòng</h1>
                <p>Danh sách chỉ hiển thị số liệu. Bấm Thêm/Sửa để mở form chi tiết.</p>
            </div>
            <?php if (!$showForm) { ?><a class="button" href="rooms.php?add=1">+ Thêm phòng mới</a><?php } ?>
        </section>

        <?php if($message){?><p class="success"><?php echo h($message);?></p><?php } ?>
        <?php if($error){?><p class="alert"><?php echo h($error);?></p><?php } ?>

        <?php if($showForm){ ?>
        <section class="room-admin-form-card room-admin-form-section">
            <div class="room-admin-form-title">
                <h2><?php echo $editing?'Sửa phòng':'Thêm phòng mới'; ?></h2>
            </div>
            <form class="room-admin-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_room">
                <?php if($editing){?><input type="hidden" name="id_phong" value="<?php echo (int)$editId;?>"><?php } ?>

                <div class="room-admin-form-grid">
                    <label><span>Loại phòng *</span>
                        <select name="id_loai_phong" required>
                            <option value="">-- Chọn loại --</option>
                            <?php foreach($roomTypes as $type){?>
                                <option value="<?php echo (int)$type['id_loai_phong'];?>" <?php echo (int)$values['id_loai_phong']===(int)$type['id_loai_phong']?'selected':'';?>><?php echo h($type['ten_loai_phong']);?> (<?php echo (int)$type['suc_chua'];?> khách)</option>
                            <?php } ?>
                        </select>
                    </label>

                    <label><span>Mã phòng *</span><input name="ma_so_phong" required value="<?php echo h($values['ma_so_phong']);?>"></label>
                    <label><span>Tên phòng *</span><input name="ten_phong" required value="<?php echo h($values['ten_phong']);?>"></label>
                    <label><span>Tầng</span><input type="number" min="1" name="tang" value="<?php echo (int)$values['tang'];?>"></label>
                    <label><span>Vị trí trên sơ đồ khu</span><input name="vi_tri" placeholder="Dãy A - tầng 1" value="<?php echo h($values['vi_tri']);?>"></label>
                    <label><span>Hướng view</span><input name="huong_view" value="<?php echo h($values['huong_view']);?>"></label>
                    <label><span>Diện tích (m²)</span><input type="number" step="0.01" min="0" name="dien_tich" value="<?php echo h($values['dien_tich']);?>"></label>
                    <label><span>Giá riêng theo đêm (bỏ trống = theo loại)</span><input type="number" step="1000" min="0" name="gia_theo_ngay" value="<?php echo h($values['gia_theo_ngay']);?>"></label>
                    <label><span>Giá riêng theo giờ (bỏ trống = theo loại)</span><input type="number" step="1000" min="0" name="gia_theo_gio" value="<?php echo h($values['gia_theo_gio']);?>"></label>
                    <label><span>Trạng thái</span>
                        <select name="trang_thai">
                            <option value="dang_hoat_dong" <?php echo $values['trang_thai']==='dang_hoat_dong'?'selected':'';?>>Hoạt động</option>
                            <option value="tu_sua" <?php echo $values['trang_thai']==='tu_sua'?'selected':'';?>>Đang tu sửa</option>
                            <option value="tam_dung" <?php echo $values['trang_thai']==='tam_dung'?'selected':'';?>>Tạm dừng</option>
                        </select>
                    </label>

                    <!-- Danh sách tiện nghi phòng -->
                    <div style="grid-column:1/-1; background:#f8fafc; border:1px solid #e2e8f0; padding:18px; border-radius:12px; margin: 4px 0;">
                        <h4 style="margin:0 0 14px; font-size:0.92rem; font-weight:700; color:#1e293b; text-transform:uppercase; letter-spacing:0.04em;">Danh sách Tiện nghi & Thiết bị phòng</h4>
                        
                        <div style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px dashed #cbd5e1;">
                            <label class="amenity-select-label">
                                <span>Số lượng giường:</span>
                                <select name="so_giuong">
                                    <option value="1" <?php echo (int)$values['so_giuong']===1?'selected':'';?>>1 giường</option>
                                    <option value="2" <?php echo (int)$values['so_giuong']===2?'selected':'';?>>2 giường</option>
                                    <option value="3" <?php echo (int)$values['so_giuong']===3?'selected':'';?>>3 giường</option>
                                </select>
                            </label>
                            <label class="amenity-select-label">
                                <span>Loại giường:</span>
                                <select name="loai_giuong">
                                    <option value="doi" <?php echo ($values['loai_giuong']??'')==='doi'?'selected':'';?>>Giường đôi</option>
                                    <option value="don" <?php echo ($values['loai_giuong']??'')==='don'?'selected':'';?>>Giường đơn</option>
                                </select>
                            </label>
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap:10px;">
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_tu_lanh" value="1" <?php echo !empty($values['co_tu_lanh'])?'checked':'';?>>
                                <span>Tủ lạnh</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_nha_bep" value="1" <?php echo !empty($values['co_nha_bep'])?'checked':'';?>>
                                <span>Nhà bếp</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_tivi_may_chieu" value="1" <?php echo !empty($values['co_tivi_may_chieu'])?'checked':'';?>>
                                <span>TiVi / Máy chiếu</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_bon_tam" value="1" <?php echo !empty($values['co_bon_tam'])?'checked':'';?>>
                                <span>Có bồn tắm</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_ban_cong" value="1" <?php echo !empty($values['co_ban_cong'])?'checked':'';?>>
                                <span>Ban công</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_sofa" value="1" <?php echo !empty($values['co_sofa'])?'checked':'';?>>
                                <span>Sofa</span>
                            </label>
                            <label class="amenity-label">
                                <input type="checkbox" class="amenity-checkbox" name="co_cua_so" value="1" <?php echo !empty($values['co_cua_so'])?'checked':'';?>>
                                <span>Cửa sổ</span>
                            </label>
                        </div>
                    </div>

                    <!-- Hình ảnh phòng & nội thất -->
                    <div style="grid-column:1/-1; background:#f8fafc; border:1px solid #e2e8f0; padding:14px 18px; border-radius:12px; margin-top:6px;">
                        <h4 style="margin:0 0 12px; font-size:0.85rem; font-weight:700; color:#1e293b; text-transform:uppercase; letter-spacing:0.04em;">Hình ảnh phòng & Nội thất</h4>
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">

                            <?php
                            $photoSlots = [
                                ['label'=>'Ảnh chính',   'name'=>'anh_phong_chinh', 'key'=>'duong_dan_hinh'],
                                ['label'=>'Nội thất 1',  'name'=>'anh_furniture_a', 'key'=>'hinh_2'],
                                ['label'=>'Nội thất 2',  'name'=>'anh_furniture_b', 'key'=>'hinh_3'],
                                ['label'=>'Nội thất 3',  'name'=>'anh_furniture_c', 'key'=>'hinh_4'],
                            ];
                            foreach ($photoSlots as $slot) {
                                $existing = !empty($editing[$slot['key']]) ? $editing[$slot['key']] : null;
                                $fullPath = $existing ? (dirname(__DIR__) . '/' . ltrim($existing, '/')) : '';
                                $hasFile  = $existing && file_exists($fullPath);
                            ?>
                            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:8px; display:flex; flex-direction:column; gap:6px;">
                                <span style="font-weight:600; font-size:0.8rem; color:#475569;"><?php echo h($slot['label']); ?></span>
                                <?php if ($hasFile) { ?>
                                    <div style="height:64px; border-radius:5px; overflow:hidden; border:1px solid #e2e8f0;">
                                        <img src="<?php echo h(asset_url('../'.$existing)); ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;" onerror="this.parentElement.style.background='#f1f5f9'; this.parentElement.innerHTML='<div style=\'height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.75rem;\'>Chưa có ảnh</div>';">
                                    </div>
                                <?php } else { ?>
                                    <div style="height:64px; border-radius:5px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; border:1px dashed #cbd5e1;">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                <?php } ?>
                                <input type="file" name="<?php echo $slot['name']; ?>" accept="image/jpeg,image/png,image/webp" style="font-size:0.75rem; width:100%;">
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="room-admin-actions">
                    <button type="submit"><?php echo $editing?'Lưu thay đổi':'Thêm phòng';?></button>
                    <a class="button button-secondary" href="rooms.php">Đóng form</a>
                </div>
            </form>
        </section>
        <?php } else { ?>
        <section class="room-admin-list-card room-admin-list-section">
            <div class="room-admin-list-intro">
                <h2>Danh sách phòng (<?php echo count($rooms); ?>)</h2>
                <p>Mỗi dòng cho biết loại, vị trí, tiện nghi chính và số lịch đang giữ chỗ.</p>
            </div>
            <div class="booking-table-wrap">
                <table class="room-table" style="table-layout:fixed; width:100%;">
                    <colgroup>
                        <col style="width:150px;">
                        <col style="width:22.5%;">
                        <col style="width:20.5%;">
                        <col style="width:100px;">
                        <col style="width:18%;">
                        <col style="width:130px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align:center;">Ảnh</th>
                            <th>Phòng</th>
                            <th>Loại phòng</th>
                            <th style="text-align:center;">SL Khách</th>
                            <th>Trạng thái</th>
                            <th style="text-align:center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rooms as $room){ ?>
                        <tr>
                            <td style="text-align:center; vertical-align:middle; padding:8px;"><img class="room-thumb" style="display:block; margin:0 auto;" src="<?php echo h(asset_url('../'.$room['hinh_dai_dien']));?>" alt="<?php echo h($room['ten_phong']);?>"></td>
                            <td>
                                <a class="room-name-link" href="rooms.php?edit_id=<?php echo (int)$room['id_phong'];?>"><strong><?php echo h($room['ten_phong']);?></strong></a><br>
                                <span style="color:#64748b; font-size:0.85rem;"><?php echo h($room['ma_so_phong']);?> · tầng <?php echo (int)$room['tang'];?></span>
                            </td>
                            <td style="font-size:0.86rem; color:#475569;"><?php echo h($room['ten_loai_phong']);?></td>
                            <td style="text-align:center; font-size:0.85rem; color:#64748b;"><?php echo (int)$room['suc_chua'];?> khách</td>
                            <td>
                                <span class="nhan-trang-thai <?php echo $room['trang_thai']==='dang_hoat_dong'?'trang-thai-con-trong':'trang-thai-tu-sua';?>"><?php echo h(ten_trang_thai_phong($room['trang_thai']));?></span><br>
                                <small style="color:#64748b; font-size:0.82rem; margin-top:4px; display:inline-block;">
                                    <?php 
                                    $nBookings = (int)$room['future_bookings'];
                                    if ($nBookings > 0) {
                                        echo 'Đã có <strong>' . $nBookings . '</strong> đơn sắp tới';
                                    } else {
                                        echo 'Chưa có đơn sắp tới';
                                    }
                                    ?>
                                </small>
                            </td>
                            <td style="text-align:center;">
                                <div class="room-action-stack">
                                    <a class="button button-small" href="rooms.php?edit_id=<?php echo (int)$room['id_phong'];?>">Sửa</a>
                                    <form method="post" onsubmit="return confirm('Xóa phòng này?');">
                                        <input type="hidden" name="action" value="delete_room">
                                        <input type="hidden" name="id_phong" value="<?php echo (int)$room['id_phong'];?>">
                                        <button type="submit" <?php echo (int)$room['future_bookings']>0?'disabled':'';?>>Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php } ?>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
