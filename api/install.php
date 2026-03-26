<?php
/**
 * 資料庫初始化腳本
 * 瀏覽器訪問一次即可建表 + 寫入預設行程資料
 * 建完後請刪除或重新命名此檔案
 */
require_once __DIR__ . '/config.php';

$db = getDB();

// ===== 建表 =====
$db->exec("
CREATE TABLE IF NOT EXISTS `itinerary` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `day`        TINYINT NOT NULL COMMENT '第幾天 1-5',
    `sort_order` INT NOT NULL DEFAULT 0,
    `time`       VARCHAR(20) NOT NULL DEFAULT '',
    `title`      VARCHAR(200) NOT NULL,
    `detail`     VARCHAR(500) DEFAULT '',
    `type`       ENUM('transport','food','attraction','shopping','hotel','flight') NOT NULL DEFAULT 'transport',
    `ticket`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已訂票',
    `lat`        DECIMAL(10,6) DEFAULT NULL,
    `lng`        DECIMAL(10,6) DEFAULT NULL,
    `map_query`  VARCHAR(200) DEFAULT '',
    `kr_name`    VARCHAR(200) DEFAULT '' COMMENT '韓文名稱',
    `kr_addr`    VARCHAR(300) DEFAULT '' COMMENT '韓文地址',
    `badges`     VARCHAR(200) DEFAULT '' COMMENT 'JSON array',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day_order (`day`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$db->exec("
CREATE TABLE IF NOT EXISTS `expenses` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `desc_text`  VARCHAR(200) NOT NULL,
    `amount`     INT NOT NULL COMMENT 'KRW',
    `category`   VARCHAR(50) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$db->exec("
CREATE TABLE IF NOT EXISTS `packing` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `item_key`   VARCHAR(100) NOT NULL UNIQUE,
    `checked`    TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ===== 檢查是否已有資料 =====
$count = $db->query("SELECT COUNT(*) FROM itinerary")->fetchColumn();
if ($count > 0) {
    jsonResponse(['status' => 'ok', 'message' => '表已存在且有資料，跳過初始化', 'itinerary_count' => $count]);
}

// ===== 寫入預設行程 =====
$days = [
    1 => [
        ['13:00','永和出門','前往桃園機場','transport',0,null,null,'','','',''],
        ['14:00','桃園機場 第一航廈','報到、安檢、候機','transport',0,null,null,'','','',''],
        ['16:40','虎航 IT210 起飛','桃園 → 金海國際機場','flight',0,null,null,'','','',''],
        ['19:55','抵達金海國際機場','入境、提領行李','transport',0,35.1795,128.9382,'김해국제공항','김해국제공항','부산광역시 강서구 공항진입로 108',''],
        ['20:55','UBER 前往飯店','車程約 35 分鐘','transport',0,35.0982,129.0325,'파라미온+호텔+부산','파라미온 호텔 부산','부산광역시 중구 광복로 55 (남포동)',''],
        ['21:30','Paramion 飯店 Check-in','','hotel',0,35.0982,129.0325,'파라미온+호텔+부산+남포동','파라미온 호텔 부산','부산광역시 중구 광복로 55',''],
        ['晚餐','南浦雪濃湯','','food',0,35.0978,129.0305,'남포설렁탕+부산','남포설렁탕','부산광역시 중구 남포동',''],
    ],
    2 => [
        ['09:30','Paramion 飯店出發','','transport',0,null,null,'','','',''],
        ['12:00','罐頭市場（早午餐）','順逛國際市場，買棉被','food',0,35.1003,129.0280,'통조림시장+부산','통조림시장 / 국제시장','부산광역시 중구 국제시장2길 25','["美食","購物"]'],
        ['—','回飯店放棉被','車程約 20 分鐘','transport',0,null,null,'','','',''],
        ['13:00','松島灣纜車站','KKday 換票入場','attraction',1,35.0685,129.0170,'송도해상케이블카+부산','부산 송도 해상케이블카','부산광역시 서구 송도해변로 171',''],
        ['13:30','岩南公園','停留約 1 小時','attraction',0,35.0632,129.0130,'암남공원+부산','','',''],
        ['14:00','松島灣纜車回程','','attraction',1,null,null,'','','',''],
        ['14:30','濟州家海鮮粥（南浦站本店）','','food',0,35.0975,129.0310,'제주가해물죽+남포역본점+부산','제주가해물죽 남포역본점','부산광역시 중구 남포동2가',''],
        ['15:30','逛大創','','shopping',0,null,null,'','','',''],
        ['17:30','回飯店放東西','','transport',0,null,null,'','','',''],
        ['傍晚','BIFF 廣場','逛逛 + 買隔天早餐','shopping',0,35.0985,129.0290,'BIFF광장+부산','','',''],
        ['晚餐','元祖糖餅','','food',0,35.0984,129.0292,'원조씨앗호떡+부산+BIFF','','',''],
    ],
    3 => [
        ['10:00','民宿早餐（麵包）','退房離開 Paramion','food',0,null,null,'','','',''],
        ['11:00','前往海雲台民宿','Airbnb 寄放行李','transport',0,null,null,'','','',''],
        ['11:30','自然島鹽麵包','','food',0,35.1590,129.1600,'자연도소금빵+해운대','자연도소금빵 해운대점','부산광역시 해운대구 해운대해변로298번길',''],
        ['12:00','海雲台藍線公園（尾浦站）','搭到青沙浦站下車','attraction',1,35.1623,129.1710,'해운대블루라인파크+미포정거장','해운대블루라인파크 미포정거장','부산광역시 해운대구 달맞이길 62번길',''],
        ['13:00','灌籃高手平交道','經典打卡點!','attraction',0,35.1610,129.1820,'청사포+슬램덩크+건널목+부산','','',''],
        ['13:20','조개뷰 海雲台本店（烤扇貝）','','food',0,35.1590,129.1630,'조개뷰+해운대본점','조개뷰 해운대본점','부산광역시 해운대구 중동1로',''],
        ['14:00','天空步道','','attraction',0,35.1587,129.1870,'청사포+다릿돌전망대','','',''],
        ['15:00-17:00','SEALIFE 水族館','','attraction',1,35.1588,129.1605,'씨라이프+부산아쿠아리움','','',''],
        ['17:00-18:30','味贊王鹽烤肉（海雲台店）','','food',0,35.1592,129.1580,'미창왕소금구이+해운대점','미창왕소금구이 해운대점','부산광역시 해운대구 구남로',''],
        ['晚間','逛街散步','Olive Young、大創、海雲台傳統市場','shopping',0,null,null,'','','',''],
    ],
    4 => [
        ['10:00','民宿早餐','','food',0,null,null,'','','',''],
        ['10:00-11:00','媽媽逛 Olive Young','小朋友玩沙沙 + 購物袋','shopping',0,35.1588,129.1592,'올리브영+해운대','','',''],
        ['11:10-12:00','回民宿放東西、換衣服','','transport',0,null,null,'','','',''],
        ['12:30-13:30','Mipo-jib 生醃螃蟹','海雲台本店','food',0,35.1615,129.1695,'미포집+해운대본점','미포집 해운대본점','부산광역시 해운대구 달맞이길',''],
        ['14:00-16:00','Skyline Luge Busan','超好玩斜坡滑車!','attraction',1,35.1860,129.2150,'스카이라인+루지+부산','스카이라인 루지 부산','부산광역시 기장군 기장읍 기장해안로 205',''],
        ['16:30 起','釜山樂天世界冒險樂園','順便逛樂天超市大買特買','attraction',1,35.1960,129.2130,'롯데월드+어드벤처+부산','','','["已訂票","購物"]'],
        ['晚餐','Puradak 炸雞','','food',0,35.1590,129.1590,'푸라닭+치킨+해운대','','',''],
        ['—','Jinwoorin Haejang 韓牛料理','','food',0,35.1595,129.1575,'진우린해장+부산+해운대','진우린해장','부산광역시 해운대구 구남로',''],
        ['—','海雲台傳統市場','','shopping',0,35.1600,129.1580,'해운대전통시장','','',''],
    ],
    5 => [
        ['09:30','海雲台民宿早餐','','food',0,null,null,'','','',''],
        ['—','海雲台道地蔘雞湯','','food',0,35.1593,129.1595,'도지삼계탕+해운대+부산','해운대 삼계탕','부산광역시 해운대구 해운대해변로',''],
        ['10:00-11:00','媽媽逛 Olive Young','小朋友玩沙沙','shopping',0,null,null,'','','',''],
        ['11:10-12:00','回民宿放東西、換衣服','12 點前退房 + 寄放行李','transport',0,null,null,'','','',''],
        ['12:30-13:30','釜山 X the SKY','','attraction',1,35.1622,129.1680,'부산엑스더스카이','부산엑스더스카이','부산광역시 해운대구 달맞이길 30 엘시티 98-100층',''],
        ['14:30','水邊最高豬肉湯飯','','food',0,35.1590,129.1590,'물가에최고돼지국밥+부산','물가에최고돼지국밥','부산광역시 해운대구',''],
        ['—','拿行李','','transport',0,null,null,'','','',''],
        ['15:30-17:00','ARTE MUSEUM','','attraction',1,35.1958,129.2128,'아르떼뮤지엄+부산','아르떼뮤지엄 부산','부산광역시 기장군 기장읍 동부산관광로 42',''],
        ['18:00','抵達金海機場','報到、安檢、免稅店','transport',0,35.1795,128.9382,'김해국제공항','김해국제공항','부산광역시 강서구 공항진입로 108',''],
        ['20:40','虎航 IT211 起飛','金海 → 桃園機場','flight',0,null,null,'','','',''],
        ['22:10','抵達桃園機場','回家！辛苦了','transport',0,null,null,'','','',''],
    ],
];

$stmt = $db->prepare("
    INSERT INTO itinerary (day, sort_order, time, title, detail, type, ticket, lat, lng, map_query, kr_name, kr_addr, badges)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$total = 0;
foreach ($days as $dayNum => $stops) {
    foreach ($stops as $i => $s) {
        $stmt->execute([
            $dayNum,
            $i * 10, // sort_order 間隔 10，方便中間插入
            $s[0],   // time
            $s[1],   // title
            $s[2],   // detail
            $s[3],   // type
            $s[4],   // ticket
            $s[5],   // lat
            $s[6],   // lng
            $s[7],   // map_query
            $s[8],   // kr_name
            $s[9],   // kr_addr
            $s[10],  // badges
        ]);
        $total++;
    }
}

jsonResponse([
    'status' => 'ok',
    'message' => "初始化完成！已建立 3 張表、寫入 {$total} 筆行程資料",
    'tables' => ['itinerary', 'expenses', 'packing'],
    'itinerary_count' => $total,
]);
