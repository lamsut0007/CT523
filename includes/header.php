<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #24302f;
            background: #f7f5ef;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .site-header {
            background: #ffffff;
            border-bottom: 1px solid #e2ded2;
        }

        .container {
            width: min(1100px, 92%);
            margin: 0 auto;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #1f5f4a;
        }

        .menu a {
            margin-left: 20px;
            font-weight: bold;
        }

        .hero {
            min-height: 400px;
            background:
                linear-gradient(rgba(17, 36, 32, 0.50), rgba(17, 36, 32, 0.40)),
                url("assets/room/3.jpg");
            background-size: cover;
            background-position: center;
            color: #ffffff;
            display: flex;
            align-items: center;
        }

        .hero-content {
            width: min(1000px, 90%);
            margin: 0 auto;
            max-width: 600px;
        }

        .hero h1 {
            margin: 8px 0 14px;
            font-size: 42px;
            line-height: 1.15;
        }

        .eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0;
            font-size: 13px;
            font-weight: bold;
            color: #ac7a35;
        }

        .button,
        button {
            display: inline-block;
            border: 0;
            border-radius: 4px;
            background: #1f5f4a;
            color: #ffffff;
            padding: 12px 18px;
            font-weight: bold;
            cursor: pointer;
        }

        main {
            width: min(1100px, 92%);
            margin: 0 auto;
        }

        .search-box,
        .section {
            margin: 32px 0;
        }

        .search-box {
            margin-top: -42px;
            position: relative;
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-radius: 6px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(30, 38, 35, 0.12);
        }

        .search-box-normal {
            margin-top: 24px;
        }

        .page-heading {
            padding: 42px 0 10px;
            max-width: 760px;
        }

        .page-heading h1 {
            margin: 8px 0 12px;
            font-size: 36px;
            line-height: 1.2;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            align-items: end;
        }

        label {
            display: block;
            font-weight: bold;
        }

        input {
            width: 100%;
            margin-top: 6px;
            padding: 11px;
            border: 1px solid #c9c4b8;
            border-radius: 4px;
            font: inherit;
        }

        .alert {
            margin: 16px 0 0;
            padding: 12px;
            border-radius: 4px;
            background: #fff3df;
            border: 1px solid #e6b773;
            color: #6d4713;
        }

        .section-title {
            max-width: 620px;
        }

        .section-note,
        .room-meta,
        .muted {
            color: #68736f;
        }

        .section-action {
            margin-top: 18px;
        }

        .room-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .room-card {
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-radius: 6px;
            overflow: hidden;
        }

        .room-image {
            height: 180px;
            background: #e8e1d5;
            overflow: hidden;
        }

        .room-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .room-info {
            padding: 18px;
        }

        .room-info h3 {
            margin-top: 0;
        }

        .badge {
            display: inline-block;
            margin: 0 0 10px;
            padding: 4px 8px;
            border-radius: 4px;
            background: #edf4f1;
            color: #1f5f4a;
            font-size: 13px;
            font-weight: bold;
        }

        .room-meta,
        .muted {
            font-size: 14px;
        }

        .price {
            color: #1f5f4a;
            font-weight: bold;
        }

        .empty {
            grid-column: 1 / -1;
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-radius: 6px;
            padding: 18px;
        }

        .home-intro {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 34px;
            align-items: center;
        }

        .home-intro h2,
        .location-band h2 {
            margin: 8px 0 12px;
            font-size: 32px;
            line-height: 1.25;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 24px;
        }

        .stats div {
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-radius: 6px;
            padding: 14px;
        }

        .stats strong {
            display: block;
            color: #1f5f4a;
            font-size: 26px;
        }

        .stats span {
            color: #68736f;
            font-size: 14px;
        }

        .image-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .image-grid img {
            display: block;
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 6px;
        }

        .image-grid .image-large {
            grid-row: span 2;
            height: 372px;
        }

        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .amenity-card {
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-radius: 6px;
            padding: 20px;
        }

        .amenity-card h3 {
            margin-top: 0;
        }

        .location-band {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2ded2;
            border-left: 6px solid #d99b42;
            border-radius: 6px;
            padding: 28px;
        }

        .location-band div {
            max-width: 680px;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
            align-items: start;
            padding: 30px 0;
            border-top: 1px solid #e2ded2;
        }

        .feature-list p {
            background: #ffffff;
            border-left: 4px solid #d99b42;
            margin: 0 0 12px;
            padding: 14px;
        }

        .site-footer {
            margin-top: 40px;
            background: #24302f;
            color: #ffffff;
            padding: 24px 0;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        @media (max-width: 800px) {
            .form-row,
            .room-list,
            .two-columns,
            .home-intro,
            .amenity-grid {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .image-grid {
                grid-template-columns: 1fr;
            }

            .image-grid .image-large,
            .image-grid img {
                height: 220px;
            }

            .location-band {
                display: block;
            }

            .menu {
                display: none;
            }

            .hero h1 {
                font-size: 32px;
            }

            .footer-content {
                display: block;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="logo" href="index.php">OASIS Homestay</a>
            <nav class="menu">
                <a href="index.php">Trang chủ</a>
                <a href="rooms.php">Phòng</a>
                <a href="#">Đặt lịch</a>
                <a href="#">Liên hệ</a>
            </nav>
        </div>
    </header>
