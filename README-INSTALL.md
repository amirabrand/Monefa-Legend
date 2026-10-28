# راهنمای نصب تراوین لجند (Travian Legend)

این پوشه ابزار نصب و تعمیر پکیج `hamid travian.zip` است. با آن:

* **۱۲ فایل خراب** پکیج (از جمله قالب اصلی صفحه) تعمیر یا قرنطینه می‌شود،
* مسیرهای `/home/cp52249/...` با مسیر واقعی سرور شما جایگزین می‌شوند،
* دیتابیس ساخته و دامپ وارد می‌شود،
* اکانت ادمین و فایل‌های کانفیگ ساخته می‌شوند،
* بک‌دور ورود، Session جعلی، کلیدهای لو رفته و فایل‌های دیباگ حذف/بی‌اثر می‌شوند.

جزئیات مشکلات در فایل [`BUGS-REPORT.md`](BUGS-REPORT.md) آمده است.

## محتوای این پوشه

**نصب فقط با یک فایل انجام می‌شود: `install.php`** (هسته‌ی نصاب و شیم `mysql_*` داخل خودش هستند).

| فایل | کار |
|------|-----|
| `install.php` | **تک‌فایل نصاب** (وب + CLI): تعمیر کد، ساخت دیتابیس، ایمپورت دامپ، ادمین، کانفیگ، حالت تعمیر. خودش `application/legacy_compat.php` و `application/lang_fallback.php` را می‌سازد |
| `install.sh` | اختیاری — پوشش لینوکس: چک پیش‌نیازها و مجوزها، بعد اجرای `install.php` |
| `fixed-files/` | همان فایل‌های تعمیرشده، آماده برای کپی دستی (اگر نمی‌خواهید نصاب را اجرا کنید) |
| `patches.diff` | دیف دقیق تغییرات همان فایل‌ها (برای مرور قبل از اعمال) |
| `docker/` | استک آماده: PHP 8.2 + MariaDB |
| `BUGS-REPORT.md` | گزارش کامل باگ‌ها با شماره خط و توضیح |

> اگر نمی‌خواهید PHP اجرا کنید، فقط محتویات `fixed-files/` را روی فایل‌های متناظر
> در پکیج کپی کنید؛ بازی دیگر آن خطاهای نحوی را ندارد. بقیه‌ی اصلاحات (دیتابیس،
> امنیت، کانفیگ و گرافیک) با نصاب انجام می‌شود.

---

## ۰) ساختار پکیج (قبل از نصب بخوانید)

فایل زیپ یک **بکاپ کامل DirectAdmin** است، نه یک پکیج نصب استاندارد:

```
hamid travian/
├── ConfsData/                    ← کانفیگ هر سرور + دامپ‌ها  (بیرون از وب‌روت)
│   ├── botConfig.php
│   ├── ss.sql                    ← دامپ ری‌اینستال خودکار
│   ├── tt1.traviana.ir/config.php  ← کانفیگ اصلی سرور بازی
│   └── tt2..tt6.traviana.ir/
├── domains/
│   ├── tt1/                      ← 🎮 موتور بازی (همین را نصب می‌کنیم)  ≈۷۱ MB
│   ├── gpack.traviana.ir/        ← 🖼️ پکیج گرافیک (gpack/js/img)  ≈۳۸۰ MB
│   ├── central.traviana.ir/      ← پنل «سرور لیست» (CodeIgniter)
│   ├── traviana.ir/              ← سایت اصلی + لابی
│   ├── answers / news / tdgpacks ← سایت‌های جانبی
│   └── test.traviana.ir/         ← نسخه‌ی قدیمی TravianZ (کار نمی‌کند، لازم نیست)
├── sql game and serverloby+goldbank/
│   ├── cp52249_tt1 new version.sql   ← ✅ دامپ بازی (۲.۲ MB) — همین را نصب می‌کنیم
│   ├── cp52249_index.sql
│   ├── cp52249_goldbankTD.sql
│   ├── cp52249_telbot.sql
│   └── ...
├── publichtml loby server index/  ← لابی/سرور لیست (Node/JS)
└── images guide install scriptin directadmin/  ← عکس‌های آموزش (بی‌فایده)
```

**حداقل چیزی که برای یک سرور بازی لازم است:** `domains/tt1` + `ConfsData` + یک دامپ SQL.
بقیه (لابی، پنل مرکزی، سایت‌ها) اختیاری‌اند.

---

## ۱) چیدمان نهایی روی سرور

```
/var/www/travian/
├── ConfsData/          ← کپی کل ConfsData (بیرون از وب‌روت، وگرنه رمز دیتابیس لو می‌رود)
├── game/               ← کپی محتویات domains/tt1  (login.php اینجاست)
├── sql/                ← دامپ .sql
└── gpack/              ← (اختیاری) کپی محتویات domains/gpack.traviana.ir/public_html
```

در cPanel/DirectAdmin معادلش:

```
/home/USER/ConfsData/          ← بیرون از public_html
/home/USER/public_html/        ← محتویات domains/tt1
```

> ⚠️ `ConfsData` هیچ‌وقت نباید داخل `public_html` باشد. نصاب هم اگر داخل وب‌روت باشد
> با `.htaccess` مسدودش می‌کند، ولی بهتر است کلاً بیرون باشد.

---

## ۲) روش الف — نصب روی هاست اشتراکی (cPanel / DirectAdmin)

1. **آپلود:** محتویات `domains/tt1` را در `public_html` بریزید (یا در زیردامنه).
2. **ConfsData:** پوشه‌ی `ConfsData` را در **یک سطح بالاتر** از `public_html` آپلود کنید
   (یعنی `~/ConfsData`).
3. **نصاب:** فقط فایل `install.php` را کنار `login.php` بگذارید (هیچ پوشه‌ی جانبی لازم نیست).
4. **دامپ SQL:** فایل `cp52249_tt1 new version.sql` را در `~/sql/` بگذارید
   (نصاب خودش پیدایش می‌کند).
5. **دیتابیس:** در cPanel یک دیتابیس + کاربر بسازید و به آن دسترسی کامل بدهید.
6. مرورگر:
   ```
   https://your-domain/install.php
   ```
   فرم را پر کنید (اطلاعات دیتابیس، دامنه، نام سرور، یوزر/رمز ادمین) و «شروع نصب» را بزنید.
7. بعد از پایان: فایل `install.php` را **حذف** کنید. فایل `install.lock` را نگه دارید.

---

## ۳) روش ب — نصب با یک دستور روی VPS (Linux)

```bash
# پیش‌نیازها (Ubuntu/Debian)
sudo apt install -y php8.2-cli php8.2-mysql php8.2-mbstring php8.2-gd php8.2-curl php8.2-zip mariadb-server

# ۱. چیدمان
sudo mkdir -p /var/www/travian/{sql,ConfsData,game}
sudo cp -r "hamid travian/domains/tt1/."            /var/www/travian/game/
sudo cp -r "hamid travian/ConfsData/."              /var/www/travian/ConfsData/
sudo cp "hamid travian/sql game and serverloby+goldbank/cp52249_tt1 new version.sql" /var/www/travian/sql/travian.sql
sudo cp -r "hamid travian/domains/gpack.traviana.ir/public_html" /var/www/travian/gpack

# ۲. نصاب (فقط یک فایل)
sudo cp travian-legend-kit/install.php /var/www/travian/game/
cd /var/www/travian/game
sudo chown -R www-data:www-data /var/www/travian

# ۳. اجرا
sudo -u www-data php install.php \
  --db-host=localhost --db-user=root --db-pass='رمز-دیتابیس' --db-name=travian \
  --host=game.example.com \
  --server-name="سرور من" --speed=3 --lang=fa \
  --admin-user=admin --admin-pass='یک-رمز-قوی' --admin-email=me@example.com
```

یا با اسکریپت پوششی (پیش‌نیازها و مجوزها را خودش چک می‌کند). `install.php` و
`install.sh` را کنار هم (یا در ریشه‌ی بازی) بگذارید:

```bash
sudo cp travian-legend-kit/install.php travian-legend-kit/install.sh /var/www/travian/game/
cd /var/www/travian/game
sudo bash install.sh --db-user=root --db-pass='...' --db-name=travian \
     --host=game.example.com --admin-pass='...'
```

**فقط تعمیر بدون نصب** (اگر قبلاً نصب کرده‌اید):

```bash
php install.php --fix-only --dry-run    # فقط نمایش تغییرات
php install.php --fix-only              # اعمال
```

یا از مرورگر: `https://your-domain/install.php?mode=fix` (و `&dry=1` برای پیش‌نمایش).

**بررسی سلامت نصب** (بعد از نصب، یا هر وقت چیزی خراب شد):

```bash
php install.php --check --db-user='...' --db-pass='...' --db-name=travian --host=game.example.com
```

```
== health check ==
  OK     ConfsData/<domain>/config.php          /var/www/travian/ConfsData/game.example.com/config.php
  OK     application/env.php                    paths + default host
  OK     application/legacy_compat.php          mysql_* shim + TB_PREFIX
  OK     application/lang_fallback.php          102 UI constants
  OK     licence-header repair                 html.php leaves PHP mode before <!DOCTYPE
  OK     database tables                        83 tables in travian
  OK     config row                             name=سرور من, speed=3, opening=2026-10-26 02:30
  OK     admin account                          1 account(s) with access>=9
```

یا از مرورگر:
`install.php?mode=check&dbUser=...&dbPass=...&dbName=...&host=game.example.com`

---

## ۴) روش ج — داکر (تمیزترین راه برای تست)

```bash
cd travian-legend-kit/docker
mkdir -p ../game ../ConfsData ../sql ../gpack
cp -r "../../hamid travian/domains/tt1/." ../game/
cp -r "../../hamid travian/ConfsData/."   ../ConfsData/
cp "../../hamid travian/sql game and serverloby+goldbank/cp52249_tt1 new version.sql" ../sql/
cp -r "../../hamid travian/domains/gpack.traviana.ir/public_html/." ../gpack/
cp ../install.php ../game/

docker compose up -d --build
docker compose exec web php /var/www/html/install.php \
    --db-host=db --db-user=root --db-pass=rootpass --db-name=travian \
    --host=localhost --admin-pass=Admin12345 \
    --gpack-url=http://localhost:8080/gpackcdn/

# http://localhost:8080/login.php
```

> در داکر، پورت ۸۰۸۰ روی `localhost` است. اگر دامنه‌ی واقعی می‌گذارید،
> `--host` را همان دامنه بدهید و یک reverse proxy جلوی کانتینر بگذارید.

---

## ۵) بعد از نصب

### الف) ساعت بازی (مهم)
قلب بازی `application/queue.php` است (ساخت ساختمان، حمله، حرکت نیروها).
این فایل از `Village.php` در **هر درخواست** هم اجرا می‌شود (با قفل یک‌ثانیه‌ای
`ConfsData/<دامنه>/queue.txt`)، پس بازی روی ترافیک کاربران جلو می‌رود.
برای وقتی که هیچ‌کس آنلاین نیست، یک کرون بگذارید:

```bash
# هر دقیقه
* * * * * /usr/bin/php /var/www/travian/game/application/queue.php >/dev/null 2>&1
```

> `application/queue.php` به‌تنهایی بوت نمی‌شود (فقط بدنه‌ی منطق است و به
> `$database` / `CONFSDATA` نیاز دارد). اگر می‌خواهید از CLI اجرا شود، از یک
> اسکریپت پوششی استفاده کنید:
> ```bash
> #!/usr/bin/env php
> <?php chdir('/var/www/travian/game'); include 'login.php';
> ```
> یا به سادگی همان کرون را روی یک URL نگه دارید:
> `curl -s https://game.example.com/dorf1.php >/dev/null`.

در cPanel: Cron Jobs → هر دقیقه → `php /home/USER/public_html/dorf1.php`.
نصاب `TRAVIAN_DEFAULT_HOST` را در `application/env.php` ست می‌کند تا کانفیگ
در CLI هم پیدا شود.

### ب) php.ini
```
memory_limit = 512M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 120
display_errors = Off
date.timezone = Asia/Tehran
```
(اعداد بزرگ‌تر برای `max_input_vars` اگر پنل ادمین لیست‌های طولانی دارد.)

### ج) nginx (اگر Apache نیستید)
```
location ^~ /application/ { deny all; return 403; }
location ~ \.sql$        { deny all; return 403; }
location ~ /\.           { deny all; return 403; }
location / { try_files $uri $uri/ /index.php?$query_string; }
```
و `ConfsData` را بیرون از `root` نگه دارید.

### د) SSL
`https://` را در `HOMEPAGE` (جدول `config`) و `homepage` ست کنید؛ بعضی صفحات
از `HOMEPAGE` برای ساخت لینک استفاده می‌کنند.

### هـ) امنیت (چک‌لیست)
- [ ] `install.php` را حذف کنید.
- [ ] `ConfsData/` بیرون از وب‌روت باشد.
- [ ] رمزهای دیتابیس/پی‌پال/زرین‌پال/SMTP داخل کانفیگ را عوض کنید (نصاب خالی‌شان می‌کند
      ولی کلیدهای قدیمی را در پنل‌های مربوطه **باطل** کنید).
- [ ] توکن ربات تلگرام قدیمی را در BotFather باطل کنید.
- [ ] کاربر `TravianDesign`/`Multihunter`/`Natars` را با رمز جدید بازنویسی کنید
      (نصاب روی اکانت ادمین این کار را می‌کند؛ بقیه را دستی).
- [ ] اگر پنل مرکزی/لابی را نصب نمی‌کنید، پوشه‌های `central.traviana.ir` و
      `publichtml loby server index` را نصب نکنید.

---

## ۶) عیب‌یابی

| نشانه | علت | راه‌حل |
|-------|-----|--------|
| `Parse error: unexpected '<'` در `html.php` | همان باگ لایسنس | `php install.php --fix-only` یا نصب با نصاب |
| صفحه‌ی سفید | `display_errors=Off` و خطای مرگبار | `logs/db-errors.log` و `error_log` سرور را ببینید؛ موقتاً `display_errors=On` |
| `Travian: server configuration not found` | دامنه در `ConfsData/<دامنه>/` نیست | نام پوشه را دقیقاً برابر دامنه کنید یا `TRAVIAN_DEFAULT_HOST` را در `application/env.php` ست کنید |
| `Access denied for user` | رمز/یوزر دیتابیس | `ConfsData/<دامنه>/config.php` خط ۸–۱۱ |
| بازی بدون عکس / CSS | `GP_LOCATION*` به CDN خارجی اشاره می‌کند | با `--gpack=/path/to/public_html` نصب کنید یا در کانفیگ آدرس‌ها را عوض کنید |
| `DivisionByZeroError` | `ADV_TIME=0` | نصاب `max(1,...)` می‌گذارد؛ در DB هم `ADV_TIME` را > ۰ کنید |
| ورود با رمز درست کار نمی‌کند | هش قدیمی `md5(pw+lower(username))` | رمز ادمین را با نصاب ست کنید (`--force`) |
| `Too many connections` / کندی | `queue.php` همزمان چند بار | فقط یک کرون، هر ۱ دقیقه |
| فارسی به‌هم‌ریخته | اتصال utf8 | `ConfsData/.../config.php` باید `utf8mb4` باشد؛ نصاب ست می‌کند |
| `Constant already defined` | `define("PLUS_TIME")` تکراری | نصاب حذف می‌کند |
| `Error: Undefined constant "NOTICE1"` (یا `FIRSTPAGE`, `B25`…) | ۱۰۲ برچسب که در هیچ فایل زبانی نیستند | نصاب `application/lang_fallback.php` را می‌سازد؛ اگر این فایل پاک شده، دوباره `--fix-only` بزنید |
| `Warning: include(Templates/...): failed to open stream` | ویوهای قدیمی به پوشه‌ی `Templates/` اشاره می‌کنند که در این پکیج نیست | خطای غیرمرگبار است؛ آن ویو را کنار بگذارید یا بلوکش را حذف کنید |
| `Undefined variable $bakermysqli` در «کد هدیه» (ajax) | include مطلق `/home/tramian/connectionbank.php` | نصاب با `new mysqli(...)` جایگزین می‌کند |
| مرگ صفحه در سیمیلاتور جنگ | کانستانت‌های `B25…B34` تعریف نشده بودند | با `lang_fallback.php` نام ساختمان‌ها اضافه می‌شود |

لاگ نصاب در خروجی ترمینال و (در حالت وب) در همان صفحه است. هر فایل ویرایش‌شده
یک نسخه‌ی `*.kit-bak` دارد؛ برای برگشت فقط پسوند را بردارید.

---

## ۷) نصب مجدد / ریست سرور

```bash
rm /var/www/travian/game/install.lock /var/www/travian/game/application/env.php
php install.php --db-pass='...' --db-name=travian --host=game.example.com \
    --admin-pass='...' --force
```
`--force` جدول‌های موجود را DROP و دامپ را از نو ایمپورت می‌کند (بازیکنان پاک می‌شوند).

---

## ۸) چه چیزهایی نصاب دست نمی‌زند

- کد بازی و منطق آن (فقط باگ‌های فهرست‌شده در `BUGS-REPORT.md`).
- تزریق SQL فراگیر در صدها فایل (`$_GET` مستقیم در کوئری). این یک بازنویسی کامل می‌خواهد.
- سیستم هش رمز (`md5`) — به‌خاطر سازگاری با کلاینت بازی.
- پنل مرکزی/لابی و سایت‌های جانبی.

---

## ۹) درباره‌ی صحت این کیت

محیطی که این کیت در آن ساخته شده **PHP نداشت**، بنابراین نصاب به‌صورت end-to-end اجرا نشده؛
به‌جایش:

1. منطق تعمیر هدرها عیناً در یک اسکریپت معادل پیاده و روی همان ۱۰ فایل واقعی اجرا شد
   ⇒ هر ۱۰ فایل تعمیر شد و اسکن مجدد **صفر** خطای نحوی داد (`fixed-files/` و `patches.diff`).
2. همه‌ی رشته‌های جست‌وجوی پچر (۲۱ مورد) در فایل‌های واقعی بررسی شدند که **موجود** باشند
   (تا پچر بی‌صدا کاری نکند).
3. الگوریتم تقسیم SQL (`splitSql`) عیناً پورت و روی دامپ واقعی اجرا شد:
   **۲۸۶ دستور** (۸۳ CREATE TABLE، ۱۴۹ ALTER TABLE، ۵۰ INSERT)، صفر دستور با کوتیشن ناقص.
4. خود `install.php` (۲٬۴۶۰ خط) و سه محتوای جاسازی‌شده در آن — قالب `application/config.php`،
   `legacy_compat.php` و `lang_fallback.php` — استخراج و جداگانه اسکن شدند: هر سه بدون خطا،
   بدون رشته‌ی بسته‌نشده و با توازن براکت‌ها.
5. فهرست ۱۰۲ کانستانت گمشده و ۴ include شکسته از اسکن واقعی خود پکیج تولید شد
   (نه حدس) و در همان درخت هم اعمال و بازبینی شد.

با این حال، اولین اجرا را روی یک دیتابیس تست انجام دهید و خروجی
(`--fix-only --dry-run` برای دیدن تغییرات) را ببینید. همه‌ی فایل‌های ویرایش‌شده
نسخه‌ی `*.kit-bak` دارند.
