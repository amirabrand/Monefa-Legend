<h1 class="titleInHeader">پلاس</h1>
        <div class="contentNavi subNavi">
         
        <div title="" class="container <?php if(!isset($_GET['id'])) {echo "active";}else{echo "normal";} ?>">
                <div class="background-start">&nbsp;</div>
                <div class="background-end">&nbsp;</div>
                <div class="content"><a href="plus.php" class="tabItem">خرید طلا</a></div>
            </div>
            <div title="" class="container <?php if(isset($_GET['id']) && $_GET['id'] == 3) {echo "active";}else{echo "normal";} ?>">
                <div class="background-start">&nbsp;</div>
                <div class="background-end">&nbsp;</div>
                <div class="content"><a href="plus.php?id=3" class="tabItem"> <?=pluss22?></a></div>
            </div>
            
            <div title="" class="container <?php if(isset($_GET['id']) && $_GET['id'] == 5) {echo "active";}else{echo "normal";} ?>">
                <div class="background-start">&nbsp;</div>
                <div class="background-end">&nbsp;</div>
                <div class="content"><a href="plus.php?id=5" class="tabItem">دعوت دوستان</a></div>
            </div>
        <?php if($session->access == 9){ ?>
            <div title="" class="container <?php if(isset($_GET['id']) && $_GET['id'] == 6) {echo "active";}else{echo "normal";} ?>">
                <div class="background-start">&nbsp;</div>
                <div class="background-end">&nbsp;</div>
                <div class="content"><a href="plus.php?id=6" class="tabItem">ساخت کد طلا</a></div>
            </div>
        <?php } ?>
            <div class="clear"></div>
        </div>