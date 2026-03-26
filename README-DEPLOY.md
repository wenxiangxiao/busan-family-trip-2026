# 釜山旅遊網站 — 部署指南

## 架構
```
index.html          ← 前端（Tailwind + Vanilla JS）
api/
  config.php        ← DB 連線設定（部署時改密碼）
  install.php       ← 初始化腳本（建表 + 寫入行程，跑完刪掉）
  itinerary.php     ← 行程 CRUD API
  expenses.php      ← 花費記帳 API
  packing.php       ← 行李清單 API
.htaccess           ← Apache 設定
```

## 部署步驟

### 1. cPanel 建立 MySQL 資料庫
- 進 cPanel → MySQL Databases
- 建立資料庫：`busan_trip`（cPanel 會加前綴，如 `demofhs_busan_trip`）
- 建立使用者 + 密碼
- 把使用者加到資料庫，權限全開

### 2. 修改 config.php
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'demofhs_busan_trip');  // ← cPanel 前綴
define('DB_USER', 'demofhs_busan');       // ← cPanel 前綴
define('DB_PASS', '你的密碼');
```

### 3. 上傳檔案
rsync 或 cPanel File Manager 上傳到 `public_html/busan/`

### 4. 初始化資料庫
瀏覽器訪問：`https://你的網域/busan/api/install.php`
看到成功訊息後，**刪除 install.php**

### 5. 修改前端 API 路徑
在 index.html 的 `<script>` 最上方找到 `API_BASE`，改成正式路徑：
```js
const API_BASE = '/busan/api';
```

### 6. 完成
訪問 `https://你的網域/busan/` 即可使用
