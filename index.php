<?php
error_reporting(0);
$a1 = md5(microtime());
$a2 = time();
include ("inc/functions.php");
include ("inc/sendmail.php");
include ("configs/config.php");
$res = mysql_connect ($mysqlhost,$mysqluser,$mysqlpass);
mysql_select_db($mysqlbase, $res);

// Проверка существования пользователя для ajax
if (isset($_GET["user_exists"])) {
    $_GET["user_exists"] = iconv("utf-8", "windows-1251", $_GET["user_exists"]);
    $response = sqla("SELECT uid FROM `users` WHERE `smuser`=\"".strtolower($_GET["user_exists"])."\"");
    if ($response === false) print "false"; else print "true";
    exit;
}
$att = '';

if (!empty($_POST)) {
    $_POST["user"] = iconv("utf-8", "windows-1251", $_POST["user"]);
    $_POST["name"] = iconv("utf-8", "windows-1251", $_POST["name"]);
    $_POST["city"] = iconv("utf-8", "windows-1251", $_POST["city"]);
    $_POST["country"] = iconv("utf-8", "windows-1251", $_POST["country"]);
    $_POST["pass"] = iconv("utf-8", "windows-1251", $_POST["pass"]);
    $_POST["pass2"] = iconv("utf-8", "windows-1251", $_POST["pass2"]);
    $err=0;
    
    $email = $_POST ["email"];
    if ($email == "" || (!eregi("^([0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-wyz][a-z](fo|g|l|m|mes|o|op|pa|ro|seum|t|u|v|z)?)$", $email))) {
        $att = "Введите корректный E-mail адрес.";
        $err=1;
    }
    
    if ($_POST["user"] == "" || strlen($_POST["user"]) < 3 || strlen($_POST["user"]) > 21 || $_POST["user"]=='невидимка') {
        $att = "Введите корректный Логин.";
        $err=1;
    }
    if (!(eregi("^[0-9a-zA-Z]+$", $_POST["user"]) || eregi("^[0-9а-яА-Я]+$", $_POST["user"]))) {
        $att = "Введите корректный логин (нельзя использовать специальные символы, точку, одновременно русские и латинские буквы).";
        $err=1;
    }
    
    if ($_POST["zakon"] == "") {
        $att = "Вы не согласились с законами.";
        $err=1;
    }
    
    if ($_POST["pass"] == "" || strlen($_POST["pass"])<6) {
        $att = "Введите корректный пароль (минимум 6 символов).";
        $err=1;
    }
    
    if ($_POST["pass"] != $_POST["pass2"]) {
        $att = "Пароли не совпадают.";
        $err=1;
    }
    
    if (@$_COOKIE["hh_reg"]) {
        $att = "Регистрация с одного компьютера только раз в 6 часов!";
        $err=1;
    }
    
    if ($_POST["check"]<>uncrypt2($_POST["asd1"],$_POST["asd2"])) {$att = "Неверный код."; $err=1;}
    if ($err<>1) {
        $row = sqla ("SELECT * FROM `users` WHERE `smuser`='".(strtolower($_POST['user']))."' or `email`='".(strtolower($_POST['email']))."'");
        if ($row ["user"] != "") {
            $att = "Такой персонаж или e-mail уже существует.";
            $err=1;
        }
        $exp = 0;
        if (@$_COOKIE["referalUID"] && $err != 1) {
            $p = sqla("SELECT uid,user,lastip FROM users WHERE uid=".intval($_COOKIE["referalUID"])."");
            if (!show_ip() or show_ip()==$p["lastip"]) {
                $att = 
                "У вас \"нехороший\" IP. (Либо HideIP, либо ваш IP совпадает с персонажем, который привёл вас в игру)";
                $err=1;
            } 
            else 
            {
                $exp = 100;
            }
        }
        if ($err != 1) {
            $ds=date("d.m.Y H:i");
            $uid = sqla("SELECT MAX(uid) FROM `users`");
            $uid = $uid[0]+1;
            sql ("INSERT INTO `chars` (`uid`) VALUES (".$uid."); ");
            $res = sql ("INSERT INTO `users` ( `user` , `pass` , `city` , `country` , `name` , `dr` , `uid` , `level` , `email` ,`ds` , `pol`,`location`,`smuser`,wears,`zeroing`,`referal_nick`,`referal_uid`,`money`,x,y,`exp`)
            VALUES ('".$_POST['user']."', '".(md5($_POST['pass']))."', '".$_POST['city']."', '".$_POST['country']."', '".$_POST['name']."', '".$_POST['dayd'].".".$_POST['monthd'].".".$_POST['yeard']."', '".$uid."', '0', '".(strtolower($_POST['email']))."' , '".$ds."'  ,'".$_POST["pol"]."','arena',LOWER('".$_POST['user']."'),'none|none|none|none|none|none|none|none|none|none|none|none|none|none|none|none|none|none|',1,'".$p["user"]."','".$p["uid"]."',1,0,0,".$exp."); ");
            if (!mysql_error()) {
                $att = ";top.Enter('".$uid."','".md5($_POST['pass'])."');";
                setcookie("hh_reg",1,tme()+21600);
                //send_mail($_POST['email'], 'Вы зарегистрировались в игре <b>The War Earth</b>. <hr> <b>Никнэйм: <i>'.$_POST['user'].'</i></b> <br> <b>Пароль: <i>'.$_POST['pass'].'</i></b><hr><center><a href=http://Thewarearth.ru><h2>thewarearth.ru</h2></a><br>не нужно отвечать на это письмо</center>', 'vlad_007@list.ru.ru');
            } else $att = "<font class=hp>Ошибка в SQL запросе.</font> ";
        }
    }
    if ($res != 1) $att = "<font color=\"red\">".$att."</font>";
    print $att;
    exit;
}

function uncrypt2($value,$key)
{
    $a=0;
    for($i=0;$i<strlen($value);$i++)
        $a += (ord($value[$i])<<(($i+23)>>1)<<1)^($key^9+$i);
    $a %= 10000;
    $a = abs($a);
    if ($a<1000) $a+=2343;
    return $a;
}
include 'common.php';
?>
<!DOCTYPE html>
<!--[if IE 8]><html class="lt-ie10 ie8 " lang="en" 
data-fouc-class-names="swift-loading"><![endif]-->
<!--[if IE 9]><html class="lt-ie10 ie9 " lang="en" 
data-fouc-class-names="swift-loading"><![endif]-->
<!--[if gt IE 9]><!-->
<html class="" data-fouc-class-names="swift-loading" lang="<?=$lang['LANGUAGE']; ?>">
<!--<![endif]-->
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
      <title><?=$lang['PAGE_TITLE']; ?></title>
      <meta name="description" content="<?=$lang['DESCRIPTION']?>" />
      <meta name='keywords' content='<?=$lang['KEYWORDS']; ?>' />
      <meta name="msapplication-TileColor" content="#00aced">
      
      <link href="./favicon.ico" rel="shortcut icon" type="image/x-icon">
      <meta name="swift-page-name" id="swift-page-name" content="front">
      
      <link rel="stylesheet" href="css/login.css?=<?=$rand=rand(19451,6987541);?>" type="text/css">
      <link rel="stylesheet" href="css/login_more.css?=<?=$rand=rand(19451,6987541);?>" type="text/css">
      
    <link rel="stylesheet" href="css/Autocompleter.css" type="text/css" media="screen" />
    <script type="text/javascript" src="js/Observer.js"></script>
    <script type="text/javascript" src="js/Autocompleter.js"></script>
    <script type="text/javascript" src="js/countries.js"></script>
    <script type="text/javascript" src="js/cities.js"></script>      

      <link rel="canonical" href="https://engarde.vaidmenuzaidimai.com/">
      <meta name="robots" content="index" />
      <meta http-equiv="CACHE-CONTROL" content="NO-STORE" />
      
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js?=<?=$rand=rand(19451,6987541);?>"></script>
    <script type="text/javascript" src="./js/dropdown.js?=<?=$rand=rand(19451,6987541);?>"></script>

    <style type="text/css">
    #log {
        float: center;
        padding: 0.5em;
        margin-left: 10px;
        border: 1px solid #d6d6d6;
        border-left-color: #e4e4e4;
        border-top-color: #e4e4e4;
        margin-top: 10px;
    }
    </style>
    
    <script id="swift_loading_indicator">document.documentElement.className=document.documentElement.className+" "+document.documentElement.getAttribute("data-fouc-class-names");</script>
        <script id="composition_state">
          (function(){function a(a){a.target.setAttribute("data-in-composition","true")}function b(a){a.target.removeAttribute("data-in-composition")}if(document.addEventListener){document.addEventListener("compositionstart",a,!1);document.addEventListener("compositionend"
,b,!1)}})();
        </script>        
      </head>
  <body class="t1 logged-out front-random-image-city-balcony front-page " dir="ltr">
<script type="text/javascript">
function setPictureStatus(pic, s) {
    var picture = $(pic);
    if (!s) {
        picture.src = 'images/bad.png';
        picture.alt = "Bad";
        fixpng(picture);
    } else {
        picture.src = 'images/ok.png';
        picture.alt = "OK";
        fixpng(picture);
    }
}

function check_user() {
    var inp_user = $('inp_user');
    if (inp_user.value.length < 3 || inp_user.value.length > 21) {
        setPictureStatus('pic_user', false);
        return;
    }
    new Ajax("/reg.php", {
        data: Object.toQueryString({user_exists: inp_user.value}),
              method: 'get',
              update: 'ajax_user_response', 
              onComplete: function() {
                  setPictureStatus('pic_user', $('ajax_user_response').innerHTML == 'false');
                  eval($('ajax_user_response').innerHTML);
              }
    }).request();
}

function check_pass() {
    var res = $('inp_pass').value.length >= 6;
    setPictureStatus('pic_pass', res);
    return res;
}

function check_pass2() {
    var res = ($('inp_pass').value.length >= 6) && ($('inp_pass').value == $('inp_pass2').value);
    setPictureStatus('pic_pass2', res);
    return res;
}

var emailRegex = new RegExp(decode64('KD86W2EtejAtOSEjJCUmJyorLz0/Xl9ge3x9fi1dKyg/OlwuW2EtejAtOSEjJCUmJyorLz0/Xl9ge3x9fi1dKykqfCIoPzpbXHgwMS1ceDA4XHgwYlx4MGNceDBlLVx4MWZceDIxXHgyMy1ceDViXHg1ZC1ceDdmXXxcXFtceDAxLVx4MDlceDBiXHgwY1x4MGUtXHg3Zl0pKiIpQCg/Oig/OlthLXowLTldKD86W2EtejAtOS1dKlthLXowLTldKT9cLikrW2EtejAtOV0oPzpbYS16MC05LV0qW2EtejAtOV0pP3xcWyg/Oig/OjI1WzAtNV18MlswLTRdWzAtOV18WzAxXT9bMC05XVswLTldPylcLil7M30oPzoyNVswLTVdfDJbMC00XVswLTldfFswMV0/WzAtOV1bMC05XT98W2EtejAtOS1dKlthLXowLTldOig/OltceDAxLVx4MDhceDBiXHgwY1x4MGUtXHgxZlx4MjEtXHg1YVx4NTMtXHg3Zl18XFxbXHgwMS1ceDA5XHgwYlx4MGNceDBlLVx4N2ZdKSspXF0p'));

//(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])

function check_email() {
    var res = emailRegex.test($('inp_email').value)
    setPictureStatus('pic_email', res);
    return res;
}
</script>  
    <div id="doc" class="">
        <div class="topbar js-topbar">
          <div id="banners" class="js-banners">
          </div>
          <div class="global-nav" data-section-term="top_nav">
            <div class="global-nav-inner">
              <div class="container">
                
        
                <ul class="nav js-global-actions">
                  <li class="home" data-global-action="t1home">
                      <a class="nav-logo-link" href="./" data-nav="front">
                        <img src="./images/engarde_icons-X24.png" title="<?=$lang['SITE_NAME'];?>" />
                      </a>
                  </li>
                </ul>
                
                <div class="pull-right">
                  <div role="search">
            <form class="form-search js-search-form" action="/search" id="global-nav-search">
              <label class="visuallyhidden" for="search-query">Search query</label>
              <input class="search-input" id="search-query" placeholder="Search" name="q" autocomplete="off" spellcheck="false" type="text">
              <span class="search-icon js-search-action">
                <button type="submit" class="icon nav-search">
                  <span class="visuallyhidden">Search</span>
                </button>
              </span>
              <input disabled="disabled" class="search-input search-hinting-input" id="search-query-hint" autocomplete="off" spellcheck="false" type="text">
      <div class="dropdown-menu typeahead">
        <div class="dropdown-caret">
          <div class="caret-outer"></div>
          <div class="caret-inner"></div>
        </div>
        <div class="dropdown-inner js-typeahead-results">
          <div class="typeahead-saved-searches">
      <ul class="typeahead-items saved-searches-list">
        
        <li class="typeahead-item typeahead-saved-search-item"><a class="js-nav" href="" data-search-query="" data-query-source="" data-ds="saved_search" tabindex="-1"><span class="icon generic-search"></span></a></li>
      </ul>
    </div>
    <ul class="typeahead-items typeahead-topics">
      
      <li class="typeahead-item typeahead-topic-item">
        <a class="js-nav" href="" data-search-query="" data-query-source="typeahead_click" data-ds="topics" tabindex="-1">
          <i class="generic-search"></i>
        </a>
      </li>
    </ul>
    
    <ul class="typeahead-items typeahead-accounts js-typeahead-accounts">
      <li data-user-id="" data-user-screenname="" data-remote="true" data-score="" class="typeahead-item typeahead-account-item js-selectable">
        <a class="js-nav" data-query-source="typeahead_click" data-search-query="" data-ds="account">
          <img class="avatar size24">
            <div class="typeahead-user-item-info">
              <span class="fullname"></span>
              <span class="js-verified hidden"><span class="icon verified"><span class="visuallyhidden">Verified account</span></span></span>
              <span class="username"><s>@</s><b></b></span>
            </div>
        </a>
      </li>
      <li class="js-selectable typeahead-accounts-shortcut js-shortcut"><a class="js-nav" href="" data-search-query="" data-query-source="typeahead_click" data-shortcut="true" data-ds="account_search"></a></li>
    </ul>
    <ul class="typeahead-items typeahead-trend-locations-list">
      
      <li class="typeahead-item typeahead-trend-locations-item"><a class="js-nav" href="" data-ds="trend_location" data-search-query="" tabindex="-1"></a></li>
    </ul>    <ul class="typeahead-items typeahead-context-list">
      
      <li class="typeahead-item typeahead-context-item"><a class="js-nav" href="" data-ds="context_helper" data-search-query="" tabindex="-1"></a></li>
    </ul>  </div>
      </div>
  </form>
          </div>
      <!-- dropdown -->    
  <div class="dropdowns"><a class="accounts"><small><?=$lang['LANGUAGE_CHANGE'];?>:</small> <span class="js-current-language"><?=$lang['LANGUAGE_PAGE'];?></span><b class="caret"></b></a>
    <div class="submenu" style="display: none;">
      <ul class="root">
        <li><a href="login.php?lang=lt">Lietuvių</a></li>
        <li><a href="login.php?lang=ru">Русский</a></li>
        <li><a href="login.php?lang=en">English</a></li>
        <li><a href="login.php?lang=lv">Latvijas</a></li>
        <li><a href="login.php?lang=ee">Eesti</a></li>
      </ul>
    </div>
  </div>
    <!-- drop down -->
                </div>
                <a id="close-all-button" class="close-all-tweets js-close-all-tweets" href="#" title="Close all open Tweets">
                  <i class="nav-breaker"></i>
                </a>
              </div>
            </div>
          </div>
        
        </div>
        <div id="page-outer">
          <div id="page-container" class=" wrapper-front white">
            <div class="front-container front-container-full-signup" id="front-container">
  <noscript>
    <div class="front-warning">
      <h3><?=$lang['JAVA_MOBILE'];?></h3>
      <p><?=$lang['JAVA_MOBILE_LINK'];?></p>
    </div>
  </noscript>

  <div class="front-warning" id="front-no-cookies-warn">
    <h3><?=$lang['JAVA_COOKIES'];?></h3>
    <p><?=$lang['JAVA_COOKIES_LINK'];?></p>
  </div>
    <?php include 'inc/background.php'; ?>
    <div class="front-bg"><img class="front-image" src="<?php echo $path . $img ?>" /></div>

    <div class="front-card">
        <div class="front-welcome">
            <div class="front-welcome-text">
              <h1><?=$lang['WELCOME'];?></h1>
              <p><?=$lang['WELCOME_NOTES'];?></p>
              <hr />
  <style type='text/css'>
      #progressbar {
      border-radius: 13px; /* (height of inner div) / 2 + padding */
      padding: 3px;
    }
    
    #progressbar div {
       background-color: orange;
       width: 15%; /* Adjust with JavaScript */
       height: 5px;
       border-radius: 10px;
    }

  </style>
  <script type="text/javascript">
function maind(){
    startdate = new Date()
    now(startdate.getYear(),startdate.getMonth(),startdate.getDate(),startdate.getHours(),startdate.getMinutes(),startdate.getSeconds())
}


function ChangeValue(number,pv){
    numberstring =""
    var j=0 
    var i=0
    while (number > 1)
     { 

        numberstring = (Math.round(number-0.5) % 10) + numberstring
        number= number / 10
        j++
        if (number > 1 && j==3) { 
            numberstring = "," + numberstring 
            j=0}
        i++
     }

     numberstring=numberstring

if (pv==1) {document.getElementById("worldpop").innerHTML=numberstring }
}


function now(year,month,date,hours,minutes,seconds){       
startdatum = new Date(year,month,date,hours,minutes,seconds)

var now = 5600000000.0
var now2 = 5690000000.0
var groeipercentage = (now2 - now) / now *100
var groeiperseconde = (now * (groeipercentage/100))/365.0/24.0/60.0/60.0 
nu = new Date ()                
schuldstartdatum = new Date (96,1,1)                            
secondenoppagina = (nu.getTime() - startdatum.getTime())/1000
totaleschuld= (nu.getTime() - schuldstartdatum.getTime())/1000*groeiperseconde + now
ChangeValue(totaleschuld,1);


timerID = setTimeout("now(startdatum.getYear(),startdatum.getMonth(),startdatum.getDate(),startdatum.getHours(),startdatum.getMinutes(),startdatum.getSeconds())",200)
}

window.onload=maind
</script>
              <div style="font-size: 11px;"><?=$lang['GLOBALPOP'];?>: <span id="worldpop" style="font-weight: bold"></span>
              <br />
<?php

$tbl_name="counter"; // Table name

// Connect to server and select database.
mysql_connect("$mysqlhost", "$mysqluser", "$mysqlpass")or die("cannot connect to server ");
mysql_select_db("$mysqlbase")or die("cannot select DB");

$sql="SELECT * FROM $tbl_name";
$result=mysql_query($sql);

$rows=mysql_fetch_array($result);
$counter=$rows['counter'];

// if have no counter value set counter = 1
if(empty($counter)){
$counter=1;
$sql1="INSERT INTO $tbl_name(counter) VALUES('$counter')";
$result1=mysql_query($sql1);
}

echo $lang['PLAYERS'].': '. $counter . '<br />'.$lang['NONPLAYERS'].': ' . $non = $counter + 95462 . '<br />'.$lang['WARZONE'].': ' . $war = $counter + (24 * rand(1,5)) . '<br />';

// count more value
$addcounter=$counter+1;
$sql2="update $tbl_name set counter='$addcounter'";
$result2=mysql_query($sql2);

mysql_close();
?>                       
              <span title="<?=$lang['AI_NOTE'];?>"><?=$lang['ENGARDEAI'];?></span>: 15%</div><div id="progressbar">
                <div title="En Garde de la Rosa - Game Artifical Inteligence - 15%"></div>
              </div>
              
            </div>

        </div>
        <div class="front-signin js-front-signin">
        <span color="red"><?php if (isset($_GET["msg"])) echo strip_tags($_GET["msg"]); ?></span>
          <form action="game.php" method="post">
            <div class="placeholding-input username">
              <input id="signin-email" class="text-input email-input" name="user" title="<?=$lang['MENU_USERNAME'];?>" autocomplete="on" tabindex="1" type="text">
              <label for="signin-email" class="placeholder"><?=$lang['MENU_USERNAME'];?></label>
            </div>
        
            <table class="flex-table password-signin">
              <tbody>
              <tr>
                <td class="flex-table-primary">
                  <div class="placeholding-input password flex-table-form">
                    <input id="signin-password" class="text-input flex-table-input" name="pass" title="<?=$lang['MENU_PASSWORD'];?>" tabindex="2" type="password">
                    <label for="signin-password" class="placeholder"><?=$lang['MENU_PASSWORD'];?></label>
                  </div>
                </td>
                <td class="flex-table-secondary">
                  <button type="submit" class="submit btn primary-btn flex-table-btn js-submit" tabindex="4">
                    <?=$lang['MENU_SIGNIN'];?>
                  </button>
                </td>
              </tr>
              </tbody>
            </table>
        
            <div class="remember-forgot">
              <label class="remember">
                <input value="1" name="remember_me" tabindex="3" type="checkbox">
                <span><?=$lang['MENU_REMEMBERME'];?></span>
              </label>
              <span class="separator">·</span>
              <a class="forgot" href="https://twitter.com/account/resend_password"><?=$lang['MENU_FORGETPSWD']?></a>
            </div>
        
            <input name="return_to_ssl" value="true" type="hidden">
        
            <input name="scribe_log" type="hidden">
            <input name="redirect_after_login" value="/" type="hidden">
            <input value="d0f56ea6f8b2dd379921f11f7d70d5759e7ba006" name="authenticity_token" type="hidden">
          </form>
        </div>
        <div id="ajax_user_response" style="visibility: hidden; position: absolute;">false</div>
        <div class="front-signup js-front-signup">
          <h2><strong><?=$lang['MENU_NEWONE'];?></strong> <?=$lang['MENU_SINGUP'];?></h2>
        
          <form method="post" id="form_reg" action="login.php">
            <div class="placeholding-input">
              <input id="signup-user-name" class="text-input" autocomplete="off" name="user" maxlength="20" type="text" onchange="check_user()">
              <label for="signup-user-name" class="placeholder"><?=$lang['ACCOUNT_FULLNAME'];?></label>
            </div>
            <div class="placeholding-input">
              <input id="signup-user-email" class="text-input email-input" onkeyup="check_email()" onchange="check_email()" autocomplete="off" name="email" type="text">
              <label for="signup-user-email" class="placeholder"><?=$lang['ACCOUNT_EMAIL'];?></label>
            </div>
            <div class="placeholding-input">
              <input id="signup-user-password" onkeyup="check_pass()" onchange="check_pass()" class="text-input" name="pass" type="password">
              <label for="signup-user-password" class="placeholder"><?=$lang['MENU_PASSWORD'];?></label>
            </div>
            <div class="placeholding-input">
              <input id="signup-user-password_1" onkeyup="check_pass2()" onchange="check_pass2()" class="text-input" name="pass2" type="password">
              <label for="signup-user-password_1" class="placeholder"><?=$lang['MENU_PASSWORD_1'];?></label>
            </div>            
             <div class="placeholding-input">
              <select id="signup-user-sex" class="text-input" name="pol" style="height: 30px;">
                <option selected="" value="na">-------</option>
                <option value="male"><?=$lang['ACCOUNT_MALE'];?></option>
                <option value="female"><?=$lang['ACCOUNT_FEMALE'];?></option>
              </select>
            </div>             
             <div class="placeholding-input">
              <input id="signup-user-data" class="text-input" name="user[user_data]" type="text">
              <label for="signup-user-data" class="placeholder"><?=$lang['ACCOUNT_DATA'];?></label>
            </div>             
              <div class="placeholding-input">
              <input id="signup-user-security" class="checkcount" name="check" type="text" maxlength="4">
              <label for="signup-user-security" class="placeholder"><?=$lang['MENU_SECURITY'];?></label>
              <a href="javascript:ch_cpth()"><img border="0" id="right-check" src="check.php?a1=<?=$a1?>&a2=<?=$a2?>" alt="Код" style="width: 60px;" id=captcha></a>
              <input type="hidden" name="asd1" size="8" class="login" value="<?=$a1;?>">
              <input type="hidden" name="asd2" size="8" class="login" value="<?=$a2;?>">           
            </div>
              <div class="placeholding-input">
              <input id="right-check" name="zakon" value="1" type="checkbox" />
              <label for="signup-user-agreement" class="placeholder check"><a href="http://www.vaidmenuzaidimai.com/forum-2-1.html" title="<?=$lang['ACCOUNT_AGREEMENT'];?>" target="_blank"><?=$lang['ACCOUNT_AGREEMENT'];?></a></label>
              
            </div>              
            <input name="name" style="width: 100%;" class="login" type=hidden>
            <input name="country" style="width: 100%" class="login" id="inp_country" type=hidden>
            <input name="city" style="width: 100%;" class="login" id="inp_city" type=hidden>
            
            <input value="" name="context" type="hidden">
            <input value="d0f56ea6f8b2dd379921f11f7d70d5759e7ba006" name="authenticity_token" type="hidden">
            <button type="submit" class="btn signup-btn">
            <?=$lang['ACCOUNT_REGISTER'];?>
            </button>
          </form>
        </div>
    </div>   
    
  <div class="footer inline-list">
    <ul>
      <li><a href="https://www.vaidmenuzaidimai.com">About</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/forum-51-1.html">Help</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/home.php?mod=space&do=blog">Blog</a><span class="dot divider"> ·</span></li>
      <li><a href="http://status.vaidmenuzaidimai.com/">Status</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.networkgate.info">Jobs</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/thread-5-1-1.html">Terms</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/thread-9-1-1.html">Privacy</a><span class="dot divider"> ·</span></li>
      <li><a href="https://business.vaidmenuzaidimai.com/">Advertisers</a><span class="dot divider"> ·</span></li>
      <li><a href="https://business.vaidmenuzaidimai.com/">Businesses</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/RPGArticle/">Media</a><span class="dot divider"> ·</span></li>
      <li><a href="https://dev.vaidmenuzaidimai.com/forum-48-1.html">Developers</a><span class="dot divider"> ·</span></li>
      <li><a href="https://res.vaidmenuzaidimai.com/forum-51-1.html">Resources</a><span class="dot divider"> ·</span></li>
      <li><a href="https://www.vaidmenuzaidimai.com/forum-53-1.html">Directory</a><span class="dot divider"> ·</span></li>
      <li><span class="copyright">© 2013 En Garde de la Rosa and Network Gate</span></li>
    </ul>
  </div>

</div>

          </div>
        </div>
          </div>
    <div class="alert-messages hidden" id="message-drawer">
        <div class="message ">
      <div class="message-inside">
        <span class="message-text"></span><a class="dismiss" href="#">×</a>
      </div>
    </div></div>
    <div class="grid hidden">
  <div class="grid-overlay"></div>
  <div class="grid-container">
    <div class="swift-media-grid">
      <div class="grid-header-content">
        <div class="header-pic"><img class="avatar"></div>
        <h2><a class="header-title js-nav"></a></h2>
        <h3><a class="header-subtitle js-nav"></a></h3>
      </div>
      <div class="grid-media ratio">
        <div class="grid-footer">
          <i class="bird-etched"></i>
        </div>
        <div class="grid-loading">
          <span class="spinner" title="Loading..."></span>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="gallery-overlay"></div>
<div class="gallery-container">
  <div class="gallery-close-target"></div>
  <div class="swift-media-gallery">
    <div class="modal-header">
      <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <a class="gridview grid-action" href="#"><span class="icon grid-icon"><span class="visuallyhidden"></span></span></a>
      <h2 class="modal-title"></h2>
    </div>
    <div class="gallery-media"></div>
    <div class="gallery-nav nav-prev">
      <span class="nav-prev-handle"></span>
    </div>
    <div class="gallery-nav nav-next">
      <span class="nav-next-handle"></span>
    </div>
    <div class="tweet-inverted gallery-tweet"></div>
  </div>
</div>

    <div class="modal-overlay"></div>
    
    
    
    <div id="goto-user-dialog" class="modal-container">
  <div class="modal modal-small draggable">
    <div class="modal-content">
      <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
      <div class="modal-header">
        <h3 class="modal-title">Go to a person's profile</h3>
      </div>

      <div class="modal-body">
        <div class="modal-inner">
          <form class="goto-user-form">
            <input class="input-block username-input" placeholder="Start typing a name to jump to a profile" type="text">
            
            
            
            <div class="dropdown-menu typeahead">
              <div class="dropdown-caret">
                <div class="caret-outer"></div>
                <div class="caret-inner"></div>
              </div>
              <div class="dropdown-inner js-typeahead-results">
                <div class="typeahead-saved-searches">
      <ul class="typeahead-items saved-searches-list">
        
        <li class="typeahead-item typeahead-saved-search-item"><a class="js-nav" href="" data-search-query="" data-query-source="" data-ds="saved_search" tabindex="-1"><span class="icon generic-search"></span></a></li>
      </ul>
    </div>
    <ul class="typeahead-items typeahead-topics">
      
      <li class="typeahead-item typeahead-topic-item">
        <a class="js-nav" href="" data-search-query="" data-query-source="typeahead_click" data-ds="topics" tabindex="-1">
          <i class="generic-search"></i>
        </a>
      </li>
    </ul>
    
    
    
    
    <ul class="typeahead-items typeahead-accounts js-typeahead-accounts">
      
      <li data-user-id="" data-user-screenname="" data-remote="true" data-score="" class="typeahead-item typeahead-account-item js-selectable">
        
        <a class="js-nav" data-query-source="typeahead_click" data-search-query="" data-ds="account">
          <img class="avatar size24">
            <div class="typeahead-user-item-info">
              <span class="fullname"></span>
              <span class="js-verified hidden"><span class="icon verified"><span class="visuallyhidden">Verified account</span></span></span>
              <span class="username"><s>@</s><b></b></span>
            </div>
        </a>
      </li>
      <li class="js-selectable typeahead-accounts-shortcut js-shortcut"><a class="js-nav" href="" data-search-query="" data-query-source="typeahead_click" data-shortcut="true" data-ds="account_search"></a></li>
    </ul>
    <ul class="typeahead-items typeahead-trend-locations-list">
      
      <li class="typeahead-item typeahead-trend-locations-item"><a class="js-nav" href="" data-ds="trend_location" data-search-query="" tabindex="-1"></a></li>
    </ul>    <ul class="typeahead-items typeahead-context-list">
      
      <li class="typeahead-item typeahead-context-item"><a class="js-nav" href="" data-ds="context_helper" data-search-query="" tabindex="-1"></a></li>
    </ul>  </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

      <div id="retweet-tweet-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
        <div class="modal-header">
          <h3 class="modal-title">En Garde this to your followers?</h3>
        </div>
  
        <div class="modal-body modal-tweet"></div>
  
        <div class="modal-footer">
          <button class="btn cancel-action">Cancel</button>
          <button class="btn primary-btn retweet-action">En Garde</button>
        </div>
      </div>
    </div>
  </div>
  <div id="delete-tweet-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
        <div class="modal-header">
          <h3 class="modal-title">Are you sure you want to delete this En Garde?</h3>
        </div>
  
        <div class="modal-body modal-tweet"></div>
  
        <div class="modal-footer">
          <button class="btn cancel-action">Cancel</button>
          <button class="btn primary-btn delete-action">Delete</button>
        </div>
      </div>
    </div>
  </div>

    
<div id="keyboard-shortcut-dialog" class="modal-container">
  <div class="close-modal-background-target"></div>
  <div class="modal modal-large draggable">
    <div class="modal-content">
      <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
      
      <div class="modal-header">
        <h3 class="modal-title">Keyboard shortcuts</h3>
      </div>

      
      <div class="modal-body">

        <div class="keyboard-shortcuts clearfix" id="keyboard-shortcut-menu">

          <table class="modal-table">
            <tbody>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">Enter</b>
                </td>
                <td class="shortcut-label">Open Tweet details</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">G</b> <b class="sc-key">F</b>
                </td>
                <td class="shortcut-label">Go to user...</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">?</b>
                </td>
                <td class="shortcut-label">This menu</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">J</b>
                </td>
                <td class="shortcut-label">Next En Garde</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">K</b>
                </td>
                <td class="shortcut-label">Previous En Garde</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">Space</b>
                </td>
                <td class="shortcut-label">Page down</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">/</b>
                </td>
                <td class="shortcut-label">Search</td>
              </tr>
              <tr>
                <td class="shortcut">
                  <b class="sc-key">.</b>
                </td>
                <td class="shortcut-label">Load new En Garde</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

    <div id="block-user-dialog" class="modal-container">
  <div class="close-modal-background-target"></div>
  <div class="modal draggable">
    <div class="modal-content">
      <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
      <div class="modal-header">
        <h3 class="modal-title">Are you sure you want to block this user?</h3>
      </div>

      <div class="modal-body modal-tweet"></div>

      <div class="modal-footer">
        <button class="btn cancel-action">Cancel</button>
        <button class="btn primary-btn block-action">Block</button>
      </div>
    </div>
  </div>
</div>

        <div id="geo-disabled-dropdown">
          <ul class="dropdown-menu" tabindex="-1">
        <li class="dropdown-caret">
          <span class="caret-outer"></span>
          <span class="caret-inner"></span>
        </li>
        <li class="geo-not-enabled-yet">
          <h2><?=$lang['LOCATION'];?></h2>
          <p><?php $f_contents = file("inc/description.txt"); echo $line = $f_contents[rand(0, count($f_contents) - 1)];?></p>
          <div>
            <button type="button" class="geo-turn-on btn primary-btn">Turn location on</button>
            <button type="button" class="geo-not-now btn-link">Not now</button>
          </div>
        </li>
      </ul>
    </div>
    
      <div id="geo-enabled-dropdown">
        <ul class="dropdown-menu" tabindex="-1">
      <li class="dropdown-caret">
        <span class="caret-outer"></span>
        <span class="caret-inner"></span>
      </li>
      <li class="geo-query-location">
        <input autocomplete="off" placeholder="Search for a neighborhood or city" type="text">
        <i class="generic-search"></i>
      </li>
      <li class="geo-dropdown-status"></li>
      <li class="dropdown-link geo-turn-off-item geo-focusable">
        <i class="close"></i>Turn off location
      </li>
    </ul>
  </div>
    
    
      <div id="profile_popup" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal modal-small draggable">
      <div class="modal-content clearfix">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
          <h3 class="modal-title">Profile summary</h3>
        </div>
  
        <div class="modal-body profile-modal">
  
        </div>
  
        <div class="loading">
          <span class="spinner-bigger"></span>
        </div>
      </div>
    </div>
  </div>  <div id="list-membership-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal modal-small draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
          <h3 class="modal-title">Your lists</h3>
        </div>
        <div class="modal-body">
          <div class="list-membership-content"></div>
          <span class="spinner lists-spinner" title="Loading…"></span>
        </div>
      </div>
    </div>
  </div>  <div id="list-operations-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal modal-medium draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
          <h3 class="modal-title">Create a new list</h3>
        </div>
        <div class="modal-body">
          
        <div class="list-editor">
          <div class="field">
            <label for="list-name">List name</label>
            <input class="text" name="name" type="text">
          </div>
          <div class="field" style="display:none">
            <label for="list-link">List link</label>
            <span></span>
          </div>
          <hr>
        
          <div class="field">
            <label for="description">Description</label>
            <textarea name="description"></textarea>
            <span class="help-text">Under 100 characters, optional</span>
          </div>
          <hr>
        
          <div class="field">
            <label for="mode">Privacy</label>
            <div class="options">
              <label for="list-public-radio">
                <input class="radio" name="mode" id="list-public-radio" value="public" checked="checked" type="radio">
                <b>Public</b> · Anyone can follow this list
              </label>
              <label for="list-private-radio">
                <input class="radio" name="mode" id="list-private-radio" value="private" type="radio">
                <b>Private</b> · Only you can access this list
              </label>
            </div>
          </div>
          <hr>
        
          <div class="list-editor-save">
            <button type="button" class="btn btn-primary update-list-button" data-list-id="">Save list</button>
          </div>
        
        </div>      </div>
      </div>
    </div>
  </div>
      <div id="activity-popup-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal draggable">
      <div class="modal-content clearfix">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>
        <div class="modal-header">
          <h3 class="modal-title"></h3>
        </div>
  
        <div class="modal-body">
          <div class="activity-tweet clearfix"></div>
          <div class="loading">
            <span class="spinner-bigger"></span>
          </div>
          <div class="activity-content clearfix"></div>
        </div>
      </div>
    </div>
  </div>

    <div id="confirm_dialog" class="modal-container">
  <div class="close-modal-background-target"></div>
  <div class="modal draggable">
    <div class="modal-content">
      <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
        <h3 class="modal-title"></h3>
      </div>
      <div class="modal-body">
        <p class="modal-body-text"></p>
      </div>
      <div class="modal-footer">
        <button class="btn" id="confirm_dialog_cancel_button"></button>
        <button id="confirm_dialog_submit_button" class="btn primary-btn modal-submit"></button>
      </div>
    </div>
  </div>
</div>

    
    
      <div id="embed-tweet-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal modal-medium draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
          <h3 class="modal-title">Embed this En Garde</h3>
        </div>
        <div class="modal-body">
          <div class="embed-code-container">
          <p>Add this Tweet to your website by copying the code below. <a href="https://dev.engarde.net/docs/embedded-tweets">Learn more</a></p>
          <form>
        
            <div class="embed-destination-wrapper">
              <div class="embed-overlay embed-overlay-spinner"><div class="embed-overlay-content"></div></div>
              <div class="embed-overlay embed-overlay-error">
                <p class="embed-overlay-content">Hmm, there was a problem reaching the server. <a href="javascript:;">Try again?</a></p>
              </div>
              <textarea class="embed-destination"></textarea>
              <div class="embed-options">
                <div class="embed-include-parent-tweet">
                  <label for="include-parent-tweet">
                    <input id="include-parent-tweet" class="include-parent-tweet" checked="checked" type="checkbox">
                    Include parent Tweet
                  </label>
                </div>
                <div class="embed-include-card">
                  <label for="include-card">
                    <input id="include-card" class="include-card" checked="checked" type="checkbox">
                    Include media
                  </label>
                </div>
              </div>
            </div>
          </form>
          <div class="embed-preview">
            <h3>Preview</h3>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>

    <div id="signin-or-signup-dialog">
      <div id="signin-or-signup" class="modal-container">
        <div class="close-modal-background-target"></div>
        <div class="modal modal-medium draggable">
          <div class="modal-content">
            <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>          <div class="modal-header">
              <h3 class="modal-title modal-long-title signup-only">Sign up for En Garde &amp; follow @<span></span></h3>
              <h3 class="modal-title not-signup-only">Sign in to En Garde</h3>
            </div>
            <div class="modal-body signup-only">
              <form action="https://engarde.net/signup" class="clearfix signup" method="post">
              <div class="holding name">
                <input autocomplete="off" name="user[name]" maxlength="20" type="text">
                <span class="holder">Full name</span>
              </div>
              <div class="holding email">
                <input class="email-input" autocomplete="off" name="user[email]" type="text">
                <span class="holder">Email</span>
              </div>
              <div class="holding password">
                <input name="user[user_password]" type="password">
                <span class="holder">Password</span>
              </div>
              <input value="" name="context" type="hidden">
              <input value="d0f56ea6f8b2dd379921f11f7d70d5759e7ba006" name="authenticity_token" type="hidden">
              <input name="follows" value="" type="hidden">
              <input class="btn signup-btn js-submit js-signup-btn" value="Sign up" type="submit">
            </form>
          </div>
            <div class="modal-body not-signup-only">
              <form action="https://engarde.net/sessions" class="signin" method="post">
              <fieldset>
  
    <div class="clearfix holding">
      <span class="username js-username holder">Username or email</span>
      <input class="js-username-field email-input" name="session[username_or_email]" autocomplete="on" tabindex="1" type="text">
      <p class="help-text-inline">Forgot your <a href="https://engarde.net/account/resend_password" tabindex="-1">username</a>?</p>
    </div>
  
    <div class="clearfix holding">
      <span class="password holder">Password</span>
      <input class="js-password-field" name="session[password]" tabindex="2" type="password">
      <p class="help-text-inline">Forgot your <a href="https://engarde.net/account/resend_password" tabindex="-1">password</a>?</p>
    </div>
  
    <input value="d0f56ea6f8b2dd379921f11f7d70d5759e7ba006" name="authenticity_token" type="hidden">
  
  </fieldset>
  <div class="clearfix">
  
    <input name="scribe_log" type="hidden">
    <input name="redirect_after_login" value="/" type="hidden">
    <input value="d0f56ea6f8b2dd379921f11f7d70d5759e7ba006" name="authenticity_token" type="hidden">
    <button type="submit" class="submit btn primary-btn" tabindex="4">Sign in</button>
  
    <fieldset class="subchck">
      <label class="remember">
        <input value="1" name="remember_me" tabindex="3" type="checkbox">
        Remember me
      </label>
    </fieldset>
  
  </div>
  <div class="divider"></div>
              <p>
                <a class="forgot" href="https://engarde.net/account/resend_password">Forgot password?</a><br>
                <a class="mobile has-sms" href="https://engarde.net/account/complete">Already using En Garde via text message?</a>
              </p>
            </form>
            <div class="signup">
                <h2>Not on En Garde? Sign up, tune into the things you care about, and get updates as they happen.</h2>
                <form action="https://engarde.net/signup" class="signup" method="get">
                <button class="btn promotional signup-btn" type="submit">Sign up »</button>
              </form>
            </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="sms-codes-dialog" class="modal-container">
    <div class="close-modal-background-target"></div>
    <div class="modal modal-medium draggable">
      <div class="modal-content">
        <button type="button" class="modal-btn modal-close js-close"><span class="icon close-medium"><span class="visuallyhidden">Close</span></span></button>      <div class="modal-header">
          <h3 class="modal-title">Two-way (sending and receiving) short codes:</h3>
        </div>
        <div class="modal-body">    
        </div>
      </div>
    </div>
  </div>    
    <div class="hidden">
    </div>  
<div id="sr-event-log" class="visuallyhidden" aria-live="assertive"></div>
    </body>
</html>
