=====================================================================
 TRAVIAN LEGEND  -  cPanel / shared-hosting package
=====================================================================

این زیپ یک نصب‌کننده‌ی تک‌فایلی دارد. ترتیب کار:

1) آپلود
   - فایل زیپ را در File Manager به public_html آپلود کنید (نه داخل پوشه‌ای دیگر).
   - روی فایل کلیک راست → Extract.
   - بعد از استخراج باید این‌ها را مستقیم داخل public_html ببینید:
        login.php   dorf1.php   application/   install.php   _setup/   gpackcdn/

2) دیتابیس
   - cPanel → MySQL Databases
     · یک دیتابیس بسازید (مثلاً travian)
     · یک کاربر بسازید + رمز قوی
     · کاربر را به دیتابیس اضافه کنید با ALL PRIVILEGES
   - نام کامل دیتابیس و کاربر معمولاً به شکل  user_travian  است.

3) نصب
   - در مرورگر باز کنید:  https://YOUR-DOMAIN/install.php
   - فرم را پر کنید (اطلاعات دیتابیس، دامنه، نام سرور، یوزر/رمز ادمین) و «شروع نصب».
   - نصاب خودش:
       · ۱۲ فایل خراب پکیج را تعمیر می‌کند
       · دامپ _setup/travian.sql را وارد می‌کند (و بعد پاکش می‌کند)
       · پوشه‌ی ConfsData را در /home/USER/ConfsData می‌سازد (بیرون از public_html)
       · اکانت ادمین را با رمز شما بازنویسی می‌کند
       · گرافیک gpackcdn را وصل می‌کند

4) بعد از نصب
   - فایل install.php را پاک کنید.
   - پوشه‌ی _setup را کامل پاک کنید.
   - ورود: https://YOUR-DOMAIN/login.php   با همان یوزر/رمز ادمین.
   - پنل مدیریت: https://YOUR-DOMAIN/TdPaneLXP/

5) تنظیمات پیشنهادی cPanel
   - MultiPHP Manager → PHP 8.1 یا 8.2 برای این دامنه
   - MultiPHP INI Editor → memory_limit=512M ، upload_max_filesize=32M ،
     post_max_size=32M ، max_execution_time=120
   - Extensions لازم: pdo_mysql ، mysqli ، mbstring ، gd ، curl ، zip
   - Cron Jobs → هر ۱ دقیقه:
        /usr/local/bin/php /home/USER/public_html/dorf1.php >/dev/null 2>&1
     (اگر مسیر php فرق داشت، در cPanel → «PHP Selector» یا «Cron» ببینید)

6) بررسی سلامت نصب
   - مرورگر: https://YOUR-DOMAIN/install.php?mode=check&dbUser=USER_DB&dbPass=PASS&dbName=USER_DB
   - یا ترمینال:  php install.php --check --db-user=... --db-pass=... --db-name=...

7) اگر جایی خراب شد
   - حالت تعمیر بدون دیتابیس:  https://YOUR-DOMAIN/install.php?mode=fix
   - همه‌ی فایل‌های ویرایش‌شده نسخه‌ی *.kit-bak دارند؛ برای برگشت فقط پسوند را بردارید.

---------------------------------------------------------------------
 نکات مهم
---------------------------------------------------------------------
* ConfsData (رمز دیتابیس) بیرون از public_html ساخته می‌شود. هیچ‌وقت آن را
  داخل public_html جابه‌جا نکنید.
* پوشه‌ی _setup حاوی دامپ دیتابیس است؛ تا وقتی نصب تمام نشده از بیرون
  با .htaccess مسدود است، ولی بعد از نصب حتماً پاکش کنید.
* اگر هاست شما اجازه‌ی نوشتن در /home/USER را نمی‌دهد، ConfsData را دستی
  در public_html بسازید؛ نصاب خودش با .htaccess مسدودش می‌کند.
* حجم گرافیک (gpackcdn) حدود ۳۷۰ مگابایت است. اگر فضا کم دارید، می‌توانید
  بعد از نصب gpackcdn/gpack/img_rtl را نگه دارید و بقیه را پاک کنید
  (بخش «اگر فضا کم است» در README-INSTALL.md).
* این پکیج برای PHP 8.1/8.2 آماده شده. روی 8.4 هم کار می‌کند ولی
  چند هشدار Deprecation می‌بینید.

موفق باشید.
