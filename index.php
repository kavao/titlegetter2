<?PHP
// URL入力するとタイトルタグを取得して返すサンプル
// 検証補助用

// 課題： twitterのスレッドの途中を正しく取得できない。（redmineに使うなら絵文字の除去や取り込み対応を正しくできない）
//       現在は、markdown形式で、twitterの印象的なつぶやきを一覧分類するためのツール。redmineなどで使っている。

// 20180115 URL入力バグフィックス
// 20180109 bootstrap3 導入
// 20180101 クリアボタン増設
// 20170919 twitter対応、改行改善
// 20170912 twitter対応改良
// 2017xxxx title2
// 20110920 CanonicalLinkをチェックする機能を付加。
// 20110407 modeコマンドにて認証モード選択を可能に。


//タイムリミットを無効にする
set_time_limit(0);


/**
 * 日本語文字列の文字エンコーディング判定(ASCII/JIS/eucJP-win/SJIS-win/UTF-8)
 */
function detect_encoding_ja( $str )
{
  $enc = mb_detect_encoding( $str, 'ASCII,JIS,eucJP-win,SJIS-win,UTF-8', TRUE );

  switch ( $enc ) {
  case FALSE    :
  case 'ASCII'  :
  case 'JIS'    :
  case 'UTF-16' :
  case 'UTF-8'  : break;
  case 'eucJP-win' :
    // ここで eucJP-win を検出した場合、eucJP-win として判定
    if ( mb_detect_encoding( $str, 'SJIS-win,UTF-8,eucJP-win', TRUE ) === 'eucJP-win' ) {
      break;
    }
    $_hint = "\xbf\xfd" . $str; // "\xbf\xfd" : EUC-JP "雀"

    // EUC-JP -> UTF-8 変換時にマッピングが変更される文字を削除( ≒ ≡ ∫ など)
    mb_regex_encoding( 'eucJP-win' );
    $_hint = mb_ereg_replace(
      "\xad[\xe2\xf5\xf6\xf7\xfa\xfb\xfc\xf0\xf1\xf2\xf5\xf6\xf7\xfa\xfb\xfc]|" .
      "\x8f\xf3[\xfd\xfe]|\x8f\xf4[\xa1-\xa8\xab\xac\xad]|\x8f\xa2\xf1",
      '', $_hint );

    $_tmp  = mb_convert_encoding( $_hint, 'UTF-8', 'eucJP-win' );
    $_tmp2 = mb_convert_encoding( $_tmp,  'eucJP-win', 'UTF-8' );
    if ( $_tmp2 === $_hint ) {
      // 例外処理( EUC-JP 以外と認識する範囲 )
      if (
        // SJIS と重なる範囲(2バイト|3バイト|iモード絵文字|1バイト文字)
        ! preg_match( '/^(?:'
        . '(?:[\x8e\xe0-\xe9][\x80-\xfc])+|'
        . '(?:\xea[\x80-\xa4])+|'
        . '(?:\x8f[\xb0-\xef][\xe0-\xef][\x40-\x7f])+|'
        . '(?:\xf8[\x9f-\xfc])+|'
        . '(?:\xf9[\x40-\x49\x50-\x52\x55-\x57\x5b-\x5e\x72-\x7e\x80-\xb0\xb1-\xfc])+|'
        . '[\x00-\x7e]+'
        . ')+$/', $str ) &&

        // UTF-8 と重なる範囲(全角英数字・記号|漢字|1バイト文字)
        ! preg_match( '/^(?:'
        . '(?:\xef[\xbc-\xbd][\x80-\xbf])+|(?:\xef\xbe[\x80-\x9f])+|(?:\xef\xbf[\xa0-\xa5])+|'
        . '(?:[\xe4-\xe9][\x8e-\x8f\xa1-\xbf][\x8f\xa0-\xef])+|'
        . '[\x00-\x7e]+'
        . ')+$/', $str )
      ) {
        // 条件式の範囲に入らなかった場合は、eucJP-win として検出
        break;
      }
      // 例外処理2(一部の頻度の多そうな熟語を eucJP-win として判定)
      // (狡猾|珈琲|琥珀|瑪瑙|碼碯|絨緞|耄碌|膃肭臍|薔薇|蜥蜴|蝌蚪)
      if ( preg_match( '/^(?:'
        . '\xe0\xc4\xe0\xd1|\xe0\xdd\xe0\xea|\xe0\xe8\xe0\xe1|\xe0\xf5\xe0\xef|'
        . '\xe2\xfb\xe2\xf5|\xe5\xb0\xe5\xcb|\xe6\xce\xe2\xf1|\xe9\xac\xe9\xaf|'
        . '\xe9\xf2\xe9\xee|\xe9\xf8\xe9\xd1|\xe7\xac\xe6\xed\xe7\xc1|'
        . '[\x00-\x7e]+'
        . ')+$/', $str )
      ) {
        break;
      }
    }

  default :
    // ここで SJIS-win と判断された場合は、文字コードは SJIS-win として判定
    $enc = mb_detect_encoding( $str, 'UTF-8,SJIS-win', TRUE );
    if ( $enc === 'SJIS-win' ) {
      break;
    }
    $enc = 'SJIS-win';

    // UTF-8 の記号と日本語の範囲の場合は UTF-8 として検出(記号|全角英数字・記号|漢字|1バイト文字)
    if ( preg_match( '/^(?:'
      . '(?:[\xc2-\xd4][\x80-\xbf])+|'
      . '(?:\xef[\xa4-\xab][\x80-\xbf])+|'
      . '(?:\xef[\xbc-\xbd][\x80-\xbf])+|'
      . '(?:\xef\xbe[\x80-\x9f])+|'
      . '(?:\xef\xbf[\xa0-\xa5])+|'
      . '(?:[\xe2-\xe9][\x80-\xbf][\x80-\xbf])+|'
      . '[\x09\x0a\x0d\x20-\x7e]+|'
      . ')+$/', $str )
      ) {
      $enc = 'UTF-8';
    }
    // UTF-8 と SJIS 2文字が重なる範囲への対処(SJIS を優先)
    if ( preg_match( '/^(?:[\xe4-\xe9][\x80-\xbf][\x80-\x9f][\x00-\x7f])+/', $str ) ) {
      $enc = 'SJIS-win';
    }
  }
  return $enc;
}


/**
 *  http通信関数 ( / BASIC認証 / digest認証対応) 20090223 digestについては未検証
 * 
 */
function getSiteCURL( $url, $type = 'http', $username = '', $password = '')
{
  $ch = curl_init();
  if ('basic' == $type) {
    // BASIC 認証
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  } elseif ('digest' == $type) {
    // Digest 認証
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  }
  // いずれでもなければ 標準
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}



/**
 *  1個のタイトルを返す
 * 
 */
function getSiteTitle( $url, $type = 'http', $username = '', $password = '')
{
  if ('http' == $type) {
    $value = getSiteCURL( $url );
  } else {
  //  $value = getSiteCURL( $url ,'basic', 'test', 'a6acQJ3zc'); // おめでた婚
    $value = getSiteCURL( $url, $type, $username, $password );
  }

  // $value の エンコードをUTF-8に置き換える
  $encoding = detect_encoding_ja( $value );
  $value =  mb_convert_encoding( $value, 'UTF-8', $encoding );

  // タイトル <title>(取得対象)</title>
  $regex = '<(?:t|T)(?:i|I)(?:t|T)(?:l|L)(?:e|E)>(.*?)</(?:t|T)(?:i|I)(?:t|T)(?:l|L)(?:e|E)>';
  if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
    $title = $match[1];
  } else {
    $title = "(取得できませんでした)";
  }

  // 見出し meta name=description 簡易版
  // タイトル <meta name="description" content="(取得対象)" />
//  $regex = '<(?:m|M)(?:e|E)(?:t|T)(?:a|A)(?:.*?)(?:name="description")(?:.*?)content="(.*?)"(?:.*?)/>';
  $regex = '<(?:m|M)(?:e|E)(?:t|T)(?:a|A)(?:.*?)(?:name=(?:\'|")description(?:\'|"))(?:.*?)content=(?:\'|")(.*?)(?:\'|")(?:.*?)/>';
  if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
    $content = $match[1];
  } else {
    $content = "(取得できませんでした)";
  }

  // 見出し meta name=keyword 簡易版
  // タイトル <meta name="keywords" content="(取得対象)" />
  $regex = '<(?:m|M)(?:e|E)(?:t|T)(?:a|A)(?:.*?)(?:name=(?:\'|")keywords(?:\'|"))(?:.*?)content=(?:\'|")(.*?)(?:\'|")(?:.*?)/>';
  if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
    $keyword = $match[1];
  } else {
    $keyword = "(取得できませんでした)";
  }

  // 見出し meta name=keyword 簡易版
  // タイトル <link rel="canonical" href="(取得対象)" />
  $regex = '<(?:l|L)(?:i|I)(?:n|N)(?:k|K)(?:.*?)(?:rel=(?:\'|")canonical(?:\'|"))(?:.*?)href=(?:\'|")(.*?)(?:\'|")(?:.*?)/>';
  if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
    $canonicallink = $match[1];
  } else {
    $canonicallink = "(取得できませんでした)";
  }

  // h1 簡易版
  // タイトル <h1([^>]*)>(取得対象)" </h1>
  $regex = '<(?:h|H)1(?:.*?)>(.*?)</(?:h|H)1>';
  if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
    $h1 = $match[1];
  } else {
    $h1 = "(取得できませんでした)";
  }

  // twitter 本文取得
  $tw = "(対象外)";
  if ( preg_match( "@://twitter.com@" , $url))
  {
      $regex = '<p class="TweetTextSize(?:.*?)>(.*?)</p>';
      if ( (preg_match( "@".$regex."@ims", $value, $match)) ) {
        $tw = $match[1];
      } else {
        $tw = "(取得できませんでした)";
      }
  }

  // 0-5
  $temp[] = $title;
  $temp[] = $content;
  $temp[] = $keyword;
  $temp[] = $canonicallink;
  $temp[] = $h1;
  $temp[] = $tw;

  return $temp;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>タイトル取得2</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
</head>
<body>
<script type="text/javascript">
<!-- ex. http://cutmail.hatenablog.com/entry/20080812/1218533262 -->
function clearFormAll() {
    for (var i=0; i<document.forms.length; ++i) {
        clearForm(document.forms[i]);
    }
    
    /* id=render_contentsの中身も削除する */
    document.getElementById("render_contents").innerHTML = "";
    
}

function clearForm(form) {
    for(var i=0; i<form.elements.length; ++i) {
        clearElement(form.elements[i]);
    }
}
function clearElement(element) {
    switch(element.type) {
        case "hidden":
        case "submit":
        case "reset":
        case "button":
        case "image":
            return;
        case "file":
            return;
        case "text":
        case "password":
        case "textarea":
            element.value = "";
            return;
        case "checkbox":
        case "radio":
            element.checked = false;
            return;
        case "select-one":
        case "select-multiple":
            element.selectedIndex = 0;
            return;
        default:
    }
}

</script>
<div class="container">
    <ol class="breadcrumb">
      <li><a href="../">Top</a></li>
      <li class="active">タイトル取得2</li>
    </ol>
    <form method="POST" action="./index.php">
        <input type="hidden" name="act" value="inp">
        <div class="form-group">
            <label for="urllist">URL LIST</label>
            <textarea class="form-control" style="min-height: 160px; resize: vertical;" id="urllist" name="urllist"  placeholder="URLを入力して下さい。"><?PHP echo($_POST["urllist"]); ?></textarea>
        </div>
        <div class="form-group row">
            <div class=" col-md-offset-1 col-md-3 col-sm-4 col-xs-5">
            <input type="button" class="btn btn-danger btn-block"  onClick="clearFormAll()" value="フォームクリア">
            </div>
            <div class=" col-md-offset-1 col-md-6 col-sm-8 col-xs-7">
            <input type="submit"  class="btn btn-primary btn-block" value="タイトル取得実行" name="B1">
            </div>
        </div>
        <div class="form-group row">
            <div class=" col-md-offset-5 col-md-6  col-sm-offset-4 col-sm-8  col-xs-offset-5 col-xs-7">
            <button class="btn btn-default btn-block" id="clip_button" type="button" name="B2" data-clipboard-target="#render_contents">クリップボード</button>
            </div>
        </div>
    </form>
    
    <div class="panel-group">
      <div class="panel panel-info">
        <div class="panel-heading">
          <h4 class="panel-title" data-toggle="collapse" href="#collapse1">
            How To Use:
          </h4>
        </div>
        <div id="collapse1" class="panel-collapse collapse">
          <div class="panel-body">
            1.1行に１URLを入力すると順番に取得する。<br />
            2. mode:none / mode:basic:(user):(pass) / mode:digest:(user):(pass) にて読み込み方法切り替え可能<br />
            <blockquote>
                <p>用例：<br/>
                mode:basic:user:password<br/>
                http://test.exaple.com/<br/>
                mode:none<br/>
                http://www.example.com/<br/>
                </p>
            </blockquote>
            (3.他にも実行順であることを利用した動作？)<br />
            課題：hostsを固有でもたせられない。<br/>
          </div>
        </div>
      </div>
    </div>

<?PHP

//echo print_r($_POST , true);
function main() {
  $type = 'http';
  $username = '';
  $password = '';

  echo "<pre id='render_contents'>";
//  echo "<table border='1'>";
//  echo "<tr><td>URL</td><td>タイトル</td><td>ディスクリプション</td><td>キーワード</td><td>カノニカルリンク</td><td>h1</td></tr>";
  
  // タブテキストとして分割する
  $matchurl =  explode("\n", $_POST["urllist"]);
  
  // 実行部
  foreach($matchurl as $key => $value) {
    $regex1 = "(http|https)://([^:/]+)(:(\d+))?(/[^#\s]*)?(#(\S+))?";
    $regex2 = "^ *mode:([^:]+):([^:]+):([^:]+)$";
    $regex3 = "^ *mode:([^:]+)$";
    if (preg_match( "@".$regex1."@i", $value, $match)) {
      // 表示
//      echo "<tr><td>";
//      echo $match[0];
//      echo "</td>";
      $temp = getSiteTitle( $match[0] , $type, $username, $password );
      
      // from twitter
      if ( preg_match( "@://twitter.com@" , $match[0])) {
          //
          $temp[0] = "TW:" . $temp[0];
      }
      // 改行を除去 -> 文字数制限する 
      $temp[0] = preg_replace('/(?:\n|\r|\r\n|&#10;)/', '', $temp[0] );
      echo "- " . mb_strimwidth($temp[0] , 0, 120 , '...') . "  <br/>";
      echo $match[0] . "<br/><br/>";
      if ( preg_match( "@://twitter.com@" , $match[0])) {
        //$temp[5] = preg_replace('/(?:\n|\r|\r\n|&#10;)/', '', $temp[5] );
        // a href リンクだけを残す
        $temp[5] = preg_replace('@<a href="(.*?)"(?:.*?)/a>@', "\n$1\n", $temp[5] );
        echo "```\n";
        echo $temp[5] . "\n";
        echo "```\n";
        
      }

    } elseif (preg_match( "@".$regex2."@i", $value, $match)) {
      // モード切替
      if ( strpos($match[1], 'basic') !== false ) {
        // basic
        $type = 'basic';
        $username = $match[2];
        $password = rtrim($match[3]);
//      echo "<tr><td colspan='4'>mode:basic:".$username.':'.$password;
//      echo "</td></tr>";
      } elseif ( strpos($match[1], 'digest') !== false ) {
        // digest
        $type = 'digest';
        $username = $match[2];
        $password = rtrim($match[3]);
//      echo "<tr><td colspan='4'>mode:digest:".$username.':'.$password;
//      echo "</td></tr>";
      }
    } elseif (preg_match( "@".$regex3."@i", $value, $match)) {
      // 認証なし
      $type = 'http';
      $username = '';
      $password = '';
//      echo "<tr><td colspan='4'>mode:none";
//      echo "</td></tr>";
    }

  }
  
//  echo "</table>";
echo "</pre>";
}

main();


?>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
    var clipboard = new Clipboard('#clip_button');
    clipboard.on('success', function(e) {
       //成功時の処理
       alert("クリップボードへのコピー完了しました");
    });
    clipboard.on('error', function(e) {
      //失敗時の処理
       alert("クリップボードへのコピー失敗");
    });
</script>
</body>
</html>
<?PHP



?>