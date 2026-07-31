<?php
include 'includes/auth.php'; require_admin_login();
include '../includes/db.php'; include '../includes/functions.php';
$pageTitle = 'Quản lý phòng'; $assetPathPrefix = '../'; $hideBrandText = true; $brandHref = 'index.php';
$headerNavItems = array(array('href'=>'index.php','label'=>'Tổng quan'),array('href'=>'rooms.php','label'=>'Quản lý phòng'),array('href'=>'bookings.php','label'=>'Quản lý đặt phòng'),array('href'=>'logout.php','label'=>'Đăng xuất'));
$message = ''; $error = ''; $showForm = isset($_GET['add']) || isset($_GET['edit_id']); $editId = (int) ($_GET['edit_id'] ?? 0);
$roomTypes = array(); $editing = null;
$values = array('id_loai_phong'=>'','ma_so_phong'=>'','ten_phong'=>'','tang'=>1,'vi_tri'=>'','huong_view'=>'','dien_tich'=>'','tien_nghi'=>'','gia_theo_gio'=>'','gia_theo_ngay'=>'','trang_thai'=>'dang_hoat_dong');
if ($conn) { $res = $conn->query('SELECT id_loai_phong, ten_loai_phong, suc_chua FROM loai_phong ORDER BY ten_loai_phong'); if ($res) while ($row=$res->fetch_assoc()) $roomTypes[]=$row; }

function upload_room_images($files, $roomCode)
{
    $result = array(); $uploadDir = dirname(__DIR__) . '/assets/uploads/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    if (!isset($files['name']) || !is_array($files['name'])) return $result;
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) continue;
        $mime = function_exists('mime_content_type') ? mime_content_type($files['tmp_name'][$i]) : '';
        if (!in_array($mime, array('image/jpeg','image/png','image/webp','image/gif'), true)) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)); $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $roomCode);
        $newName = $safe . '-' . ($i + 1) . '-' . time() . '.' . $ext;
        if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $newName)) $result[] = 'assets/uploads/' . $newName;
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_room') {
        $id = (int) ($_POST['id_phong'] ?? 0); $type = (int) ($_POST['id_loai_phong'] ?? 0); $code = trim($_POST['ma_so_phong'] ?? ''); $name = trim($_POST['ten_phong'] ?? '');
        $tang = max(1, (int) ($_POST['tang'] ?? 1)); $position = trim($_POST['vi_tri'] ?? ''); $view = trim($_POST['huong_view'] ?? ''); $area = (float) ($_POST['dien_tich'] ?? 0); $amenities = trim($_POST['tien_nghi'] ?? ''); $priceHour = trim($_POST['gia_theo_gio'] ?? '') === '' ? null : (float) $_POST['gia_theo_gio']; $priceDay = trim($_POST['gia_theo_ngay'] ?? '') === '' ? null : (float) $_POST['gia_theo_ngay']; $status = in_array($_POST['trang_thai'] ?? '', array('dang_hoat_dong','tu_sua','tam_dung'), true) ? $_POST['trang_thai'] : 'dang_hoat_dong';
        $values = array('id_loai_phong'=>$type,'ma_so_phong'=>$code,'ten_phong'=>$name,'tang'=>$tang,'vi_tri'=>$position,'huong_view'=>$view,'dien_tich'=>$area,'tien_nghi'=>$amenities,'gia_theo_gio'=>$priceHour,'gia_theo_ngay'=>$priceDay,'trang_thai'=>$status);
        if ($type <= 0 || $code === '' || $name === '') $error = 'Vui lòng nhập đủ mã phòng, tên phòng và loại phòng.';
        else {
            $check = $conn->prepare('SELECT id_phong FROM phong WHERE ma_so_phong=? AND id_phong<>?'); $check->bind_param('si',$code,$id); $check->execute(); $check->store_result(); $duplicate=$check->num_rows>0; $check->close(); if ($duplicate) $error='Mã phòng đã tồn tại.';
        }
        if ($error === '') {
            $images = upload_room_images($_FILES['anh_phong'] ?? array(), $code);
            if ($id > 0) {
                $old = lay_phong_theo_id($conn, $id, false); $imageValues = array($old['duong_dan_hinh'],$old['hinh_2'],$old['hinh_3'],$old['hinh_4']); foreach ($images as $idx=>$image) if ($idx<4) $imageValues[$idx]=$image;
                $stmt=$conn->prepare('UPDATE phong SET id_loai_phong=?, ma_so_phong=?, ten_phong=?, tang=?, vi_tri=?, huong_view=?, dien_tich=?, tien_nghi=?, duong_dan_hinh=?, hinh_2=?, hinh_3=?, hinh_4=?, gia_theo_gio=?, gia_theo_ngay=?, trang_thai=? WHERE id_phong=?');
                $stmt->bind_param('ississdsssssddsi',$type,$code,$name,$tang,$position,$view,$area,$amenities,$imageValues[0],$imageValues[1],$imageValues[2],$imageValues[3],$priceHour,$priceDay,$status,$id);
                if ($stmt->execute()) $message='Đã cập nhật thông tin phòng.'; else $error='Không cập nhật được phòng. Hãy kiểm tra migration database.'; $stmt->close();
            } else {
                $mainImage = $images[0] ?? 'assets/room/1.jpg'; $image2=$images[1]??null; $image3=$images[2]??null; $image4=$images[3]??null;
                $stmt=$conn->prepare('INSERT INTO phong (id_loai_phong,ma_so_phong,ten_phong,tang,vi_tri,huong_view,dien_tich,tien_nghi,duong_dan_hinh,hinh_2,hinh_3,hinh_4,gia_theo_gio,gia_theo_ngay,trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ississdsssssdds',$type,$code,$name,$tang,$position,$view,$area,$amenities,$mainImage,$image2,$image3,$image4,$priceHour,$priceDay,$status);
                if ($stmt->execute()) $message='Đã thêm phòng mới.'; else $error='Không thêm được phòng. Hãy kiểm tra dữ liệu.'; $stmt->close();
            }
            if ($message !== '') { header('Location: rooms.php?msg=' . urlencode($message)); exit; }
        }
    } elseif ($action === 'delete_room') {
        $id=(int)($_POST['id_phong']??0); $check=$conn->prepare("SELECT COUNT(*) FROM dat_phong WHERE id_phong=? AND trang_thai IN ('da_xac_nhan','da_nhan_phong') AND ngay_tra_phong>NOW()"); $check->bind_param('i',$id); $check->execute(); $check->bind_result($count); $check->fetch(); $check->close();
        if ($count>0) $error='Phòng còn lịch đặt trong tương lai nên không thể xóa. Hãy chuyển sang Tạm dừng.'; else { $stmt=$conn->prepare('DELETE FROM phong WHERE id_phong=?'); $stmt->bind_param('i',$id); $message=$stmt->execute()?'Đã xóa phòng.':'Không thể xóa phòng đã có lịch sử đặt.'; $stmt->close(); }
        if ($error==='' && $message!=='') { header('Location: rooms.php?msg='.urlencode($message)); exit; }
    }
}
if (isset($_GET['msg'])) $message=$_GET['msg'];
if ($editId > 0 && $conn) { $editing=lay_phong_theo_id($conn,$editId,false); if ($editing) { $showForm=true; foreach ($values as $key=>$unused) if (array_key_exists($key,$editing)) $values[$key]=$editing[$key]; $values['gia_theo_gio']=$editing['gia_phong_gio']; $values['gia_theo_ngay']=$editing['gia_phong_ngay']; } }
$rooms=array(); if ($conn) { $res=$conn->query("SELECT p.*,lp.ten_loai_phong,lp.suc_chua,(SELECT COUNT(*) FROM dat_phong d WHERE d.id_phong=p.id_phong AND d.trang_thai IN ('da_xac_nhan','da_nhan_phong') AND d.ngay_tra_phong>NOW()) AS future_bookings FROM phong p INNER JOIN loai_phong lp ON lp.id_loai_phong=p.id_loai_phong ORDER BY p.tang,p.ma_so_phong"); if ($res) while($row=$res->fetch_assoc()) $rooms[]=$row; }
include '../includes/header.php';
?>
<main class="admin-main"><div class="admin-container"><section class="page-title"><div><h1>Quản lý phòng</h1><p>Danh sách chỉ hiển thị số liệu. Bấm Thêm/Sửa để mở form chi tiết.</p></div><?php if (!$showForm) { ?><a class="button" href="rooms.php?add=1">+ Thêm phòng mới</a><?php } ?></section><?php if($message){?><p class="success"><?php echo h($message);?></p><?php } ?><?php if($error){?><p class="alert"><?php echo h($error);?></p><?php } ?>
<?php if($showForm){ ?><section class="room-admin-form-card room-admin-form-section"><div class="room-admin-form-title"><h2><?php echo $editing?'Sửa phòng':'Thêm phòng mới'; ?></h2><p>Nhập vị trí, diện tích, tiện nghi và tối đa 4 ảnh các góc phòng.</p></div><form class="room-admin-form" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="save_room"><?php if($editing){?><input type="hidden" name="id_phong" value="<?php echo (int)$editId;?>"><?php } ?><div class="room-admin-form-grid"><label><span>Loại phòng *</span><select name="id_loai_phong" required><option value="">-- Chọn loại --</option><?php foreach($roomTypes as $type){?><option value="<?php echo (int)$type['id_loai_phong'];?>" <?php echo (int)$values['id_loai_phong']===(int)$type['id_loai_phong']?'selected':'';?>><?php echo h($type['ten_loai_phong']);?> (<?php echo (int)$type['suc_chua'];?> khách)</option><?php } ?></select></label><label><span>Mã phòng *</span><input name="ma_so_phong" required value="<?php echo h($values['ma_so_phong']);?>"></label><label><span>Tên phòng *</span><input name="ten_phong" required value="<?php echo h($values['ten_phong']);?>"></label><label><span>Tầng</span><input type="number" min="1" name="tang" value="<?php echo (int)$values['tang'];?>"></label><label><span>Vị trí trên sơ đồ khu</span><input name="vi_tri" placeholder="Dãy A - tầng 1" value="<?php echo h($values['vi_tri']);?>"></label><label><span>Hướng view</span><input name="huong_view" value="<?php echo h($values['huong_view']);?>"></label><label><span>Diện tích (m²)</span><input type="number" step="0.01" min="0" name="dien_tich" value="<?php echo h($values['dien_tich']);?>"></label><label><span>Giá riêng theo đêm (bỏ trống = theo loại)</span><input type="number" step="1000" min="0" name="gia_theo_ngay" value="<?php echo h($values['gia_theo_ngay']);?>"></label><label><span>Giá riêng theo giờ (bỏ trống = theo loại)</span><input type="number" step="1000" min="0" name="gia_theo_gio" value="<?php echo h($values['gia_theo_gio']);?>"></label><label><span>Trạng thái</span><select name="trang_thai"><option value="dang_hoat_dong" <?php echo $values['trang_thai']==='dang_hoat_dong'?'selected':'';?>>Hoạt động</option><option value="tu_sua" <?php echo $values['trang_thai']==='tu_sua'?'selected':'';?>>Đang tu sửa</option><option value="tam_dung" <?php echo $values['trang_thai']==='tam_dung'?'selected':'';?>>Tạm dừng</option></select></label><label style="grid-column:1/-1"><span>Thiết bị / tiện nghi</span><textarea class="bk-input" name="tien_nghi" rows="3" placeholder="Máy lạnh, tủ lạnh, phòng tắm riêng..."><?php echo h($values['tien_nghi']);?></textarea></label><label style="grid-column:1/-1"><span>Ảnh phòng (chọn tối đa 4 ảnh, mỗi ảnh tối đa 5MB)</span><input type="file" name="anh_phong[]" accept="image/jpeg,image/png,image/webp" multiple></label></div><div class="room-admin-actions"><button type="submit"><?php echo $editing?'Lưu thay đổi':'Thêm phòng';?></button><a class="button button-secondary" href="rooms.php">Đóng form</a></div></form></section><?php } else { ?><section class="room-admin-list-card room-admin-list-section"><div class="room-admin-list-intro"><h2>Danh sách phòng (<?php echo count($rooms); ?>)</h2><p>Mỗi dòng cho biết loại, vị trí, tiện nghi chính và số lịch đang giữ chỗ.</p></div><div class="booking-table-wrap"><table class="room-table"><thead><tr><th>Ảnh</th><th>Phòng / vị trí</th><th>Loại</th><th>Thông tin</th><th>Tiện nghi</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach($rooms as $room){?><tr><td><img class="room-thumb" src="<?php echo h(asset_url('../'.$room['duong_dan_hinh']));?>" alt="<?php echo h($room['ten_phong']);?>"></td><td><a class="room-name-link" href="rooms.php?edit_id=<?php echo (int)$room['id_phong'];?>"><strong><?php echo h($room['ten_phong']);?></strong></a><br><span><?php echo h($room['ma_so_phong']);?> · tầng <?php echo (int)$room['tang'];?></span><br><small><?php echo h($room['vi_tri']?:'Chưa gắn vị trí');?></small></td><td><?php echo h($room['ten_loai_phong']);?><br><?php echo (int)$room['suc_chua'];?> khách</td><td><?php echo h($room['dien_tich']?:'—');?> m²<br><?php echo h($room['huong_view']?:'Chưa có view');?></td><td><small><?php echo h($room['tien_nghi']?:'Chưa cập nhật');?></small></td><td><span class="nhan-trang-thai <?php echo $room['trang_thai']==='dang_hoat_dong'?'trang-thai-con-trong':'trang-thai-tu-sua';?>"><?php echo h(ten_trang_thai_phong($room['trang_thai']));?></span><br><small><?php echo (int)$room['future_bookings'];?> lịch tương lai</small></td><td><div class="room-action-stack"><a class="button button-small" href="rooms.php?edit_id=<?php echo (int)$room['id_phong'];?>">Sửa</a><form method="post" onsubmit="return confirm('Xóa phòng này?');"><input type="hidden" name="action" value="delete_room"><input type="hidden" name="id_phong" value="<?php echo (int)$room['id_phong'];?>"><button type="submit" <?php echo (int)$room['future_bookings']>0?'disabled':'';?>>Xóa</button></form></div></td></tr><?php } ?></tbody></table></div></section><?php } ?></div></main>
<?php include '../includes/footer.php'; ?>
