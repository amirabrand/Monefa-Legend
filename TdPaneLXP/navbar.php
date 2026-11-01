<div class="wrapper d-flex align-items-stretch">
  <nav id="sidebar">
    <div class="custom-menu">
      <button type="button" id="sidebarCollapse" class="btn btn-primary">
        <i class="fa fa-bars"></i>
        <span class="sr-only">باز و بسته کردن منو</span>
      </button>
    </div>
    <div class="p-4 pt-5">
      <h1><a href="index.php" class="logo">پنل مدیریت مونفا</a></h1>

      <ul class="list-unstyled components mb-5">
        <li><a href="index.php"><?php echo $lang['Admin']['sidebar01']; ?></a></li>
        <li>
          <a href="#homeSubmenu" data-toggle="collapse" aria-expanded="false"
            class="dropdown-toggle"><?php echo $lang['Admin']['sidebar02']; ?></a>
          <ul class="collapse list-unstyled" id="homeSubmenu">
            <li><a href="?p=players"><?php echo $lang['Admin']['sidebar03']; ?></a></li>
            <li><a href="?p=online"><?php echo $lang['Admin']['sidebar04']; ?></a></li>
            <li><a href="?p=notregistered"><?php echo $lang['Admin']['sidebar05']; ?></a></li>
            <li><a href="?p=multi"><?php echo $lang['Admin']['sidebar06']; ?></a></li>
            <li><a href="../massmessage.php"><?php echo $lang['Admin']['sidebar07']; ?></a></li>
            <li><a href="?p=pmails"><?php echo $lang['Admin']['sidebar09']; ?></a></li>
            <li><a href="?p=ban"><?php echo $lang['Admin']['sidebar11']; ?></a></li>

          </ul>
        </li>
        <li>
          <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false"
            class="dropdown-toggle"><?php echo $lang['Admin']['sidebar12']; ?></a>
          <ul class="collapse list-unstyled" id="pageSubmenu">
            <li><a href="?p=search"><?php echo $lang['Admin']['sidebar13']; ?></a></li>
            <li><a href="?p=message"><?php echo $lang['Admin']['sidebar14']; ?></a></li>
          </ul>
        </li>
        <li>
          <a href="#goldSubmenu" data-toggle="collapse" aria-expanded="false"
            class="dropdown-toggle"><?php echo $lang['Admin']['sidebar15']; ?></a>
          <ul class="collapse list-unstyled" id="goldSubmenu">
            <li><a href="?p=gold"><?php echo $lang['Admin']['sidebar16']; ?></a></li>
            <li><a href="?p=usergold"><?php echo $lang['Admin']['sidebar17']; ?></a></li>
            <li><a href="?p=mgold"><?php echo $lang['Admin']['sidebar10']; ?></a></li>
            <li><a href="?p=recharge">شارژ طلا برای همه</a></li>
            <li><a href="?p=codes">تولید کد طلا</a></li>
            <li><a href="?p=goldpacks">بسته‌های طلا و قیمت</a></li>
          </ul>
        </li>
        <li>
          <a href="#impoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i
              class="fa fa-setting"></i> <?php echo $lang['Admin']['sidebar18']; ?></a>
          <ul class="collapse list-unstyled" id="impoSubmenu">
            <li><a href="?p=register"><?php echo $lang['Admin']['sidebar19']; ?></a></li>
            <li><a href="?p=empoasis"><?php echo $lang['Admin']['sidebar20']; ?></a></li>
            <li><a href="?p=farms"><?php echo $lang['Admin']['sidebar21']; ?></a></li>
            <li><a href="?p=endless">تغییر سبک بازی به بی پایان</a></li>
          
            <li><a href="?p=reset">بازنشانی دیتابیس</a></li>
            <li><a href="?p=reset3">نصب مجدد</a></li>

          </ul>
        </li>

        <li><a href="?p=allmsgs"><?php echo $lang['Admin']['sidebar22']; ?></a></li>
        <li>
          <a href="#settingSubmenu" data-toggle="collapse" aria-expanded="false"
            class="dropdown-toggle"><?php echo $lang['Admin']['sidebar23']; ?></a>
          <ul class="collapse list-unstyled" id="settingSubmenu">
            <li><a href="?p=setting"><?php echo $lang['Admin']['sidebar24']; ?></a></li>
            <li><a href="?p=natars"><?php echo $lang['Admin']['sidebar25']; ?></a></li>
            <li><a href="?p=plus"><?php echo $lang['Admin']['sidebar26']; ?></a></li>
            <li><a href="?p=payments">عملیات پرداخت</a></li>
            <li><a href="?p=balebot">بات بله و پرداخت</a></li>
            <li><a href="?p=news"><?php echo $lang['Admin']['sidebar222']; ?></a></li>
            <li><a href="?p=outnews"><?php echo $lang['Admin']['sidebar2222']; ?></a></li>
            <li><a href="?p=autoauctions"><?php echo $lang['Admin']['sidebar221']; ?></a></li>
            <li><a href="?p=global"><?php echo $lang['Admin']['sidebar08']; ?></a></li>

          </ul>
        </li>
        <li><a href="?p=email">ایمیل سرور</a></li>
        <li><a href="?p=serverstatus">وضعیت سرور (CPU / RAM / هارد)</a></li>
        <li><a href="?p=support">پیام‌های پشتیبانی</a></li>
        <li><a href="../dorf1.php"><?php echo $lang['Admin']['sidebar27']; ?></a></li>
        <li><a href="../logout.php"><?php echo $lang['Admin']['sidebar28']; ?></a></li>
      </ul>


      <div class="footer">
        <?php $isOkay = TRUE; ?>
        <p><?php echo $lang['Admin']['sidebar29']; ?> <a style="color:white;"
            href="https://monefa.net">Monefa Legend</a>.</p>
      </div>

    </div>
  </nav>