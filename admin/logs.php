<?php
  # Part of the user manager section:
  # Log analyzer - analyze all logs

  $features=array('database','theme','security');
  require "../main.inc";
  $cameralife->base_url = dirname($cameralife->base_url);

  $cameralife->Security->authorize('admin_customize', 1); // Require

  if (!$_POST['showphotos'] && !$_POST['showalbums'] && !$_POST['showusers'] && !$_POST['showpreferences'])
  {
    $_POST['showphotos'] = TRUE;
    $_POST['showalbums'] = TRUE;
    $_POST['showusers'] = TRUE;
    $_POST['showpreferences'] = TRUE;
  }
  if ($_POST['showphotos'] && $_POST['showalbums'] && $_POST['showusers'] && $_POST['showpreferences'])
    $showallparts = TRUE;
  if (!$_POST['showme'] && !$_POST['showreg'] && !$_POST['showunreg'])
  {
    $_POST['showme'] = TRUE;
    $_POST['showreg'] = TRUE;
    $_POST['showunreg'] = TRUE;
  }
  if ($_POST['showme'] && $_POST['showreg'] && $_POST['showunreg'])
    $showallusers = TRUE;

  if ($_POST['action'] == 'Commit Changes')
  {
    foreach ($_POST as $var => $val)
    {
      if (!is_numeric($var) || !is_numeric($val))
        continue;

      $result = $cameralife->Database->Select('logs','*',"id=$val");
      $record = $result->FetchAssoc();

      $action = array($record['value_field']=>$record['value_old']);
      $cameralife->Database->Update($record['record_type'].'s',$action,'id='.$record['record_id']);

      $condition = "record_type='".$record['record_type']."'
                    AND value_field='".$record['value_field']."'
                    AND record_id = '".$record['record_id']."'
                    AND id >= ".$record['id'];
      $cameralife->Database->Delete('logs',$condition);
    }
  } else if ($_POST['action'] == 'Set Checkpoint') {
    $cameralife->preferences['core']['checkpoint'] = $_POST['checkpoint'];
    $cameralife->SavePreferences();
  }
?>

<html>
<head>
  <title><?= $cameralife->preferences['core']['sitename'] ?> - User Manager</title>
  <?php if($cameralife->Theme->cssURL()) {
    echo '  <link rel="stylesheet" href="'.$cameralife->Theme->cssURL()."\">\n";
  } ?>
  <meta http-equiv="Content-Type" content="text/html; charset= ISO-8859-1">
  <STYLE type="text/css">
    h2{font-size:medium}
  </STYLE>
  <script language="javascript">
  <!--
    function toggleParts(a,b) 
    {
      document.getElementById(a).style.display = 'block'
      document.getElementById(b).style.display = 'none'
      document.getElementById('showphotos').checked = 'true'
      document.getElementById('showalbums').checked = 'true'
      document.getElementById('showusers').checked = 'true'
      document.getElementById('showpreferences').checked = 'true'
      return false;
    }
    function toggleUsers(a,b) 
    {
      document.getElementById(a).style.display = 'block'
      document.getElementById(b).style.display = 'none'
      document.getElementById('showme').checked = 'true'
      document.getElementById('showreg').checked = 'true'
      document.getElementById('showunreg').checked = 'true'
      return false;
    }
  -->
  </script>
</head>
<body>
<form method="post">

<?php
  $menu = array();
  $menu[] = array("name"=>$cameralife->preferences['core']['siteabbr'],
                  "href"=>"../index.php",
                  'image'=>'small-main');
  $menu[] = array("name"=>"Administration",
                  "href"=>"index.php",
                  'image'=>'small-admin');
  $menu[] = array("name"=>"Help with Logs",
                  "href"=>"../setup/checkpoints.html",
                  'image'=>'small-admin');

  $cameralife->Theme->TitleBar("Log Viewer",
                                'admin',
                                "View logs and rollback changes to the site",
                                $menu);
?>
  <div id="allparts" <?= ($showallparts) ? '' : 'style="display:none"' ?>>
  <h2>Show changes affecting any part of the site <a href="#" onclick="toggleParts('someparts','allparts')">[change]</a></h2>
  </div>
  <div id="someparts" <?= ($showallparts) ? 'style="display:none"' : '' ?>>
  <h2>Show changes affecting only these parts of the site <a href="#" onclick="toggleParts('allparts','someparts')">[show all]</a></h2>

  <table width="100%">
    <tr>
      <td width="25%">
        <input type="checkbox" id="showphotos" name="showphotos"
          <?php if ($_POST["showphotos"]) echo " checked" ?>
        >
        <label for="showphotos">
          <?php $cameralife->Theme->Image('small-photo') ?>Photos
        </label>
      <td width="25%">
        <input type="checkbox" id="showalbums" name="showalbums"
          <?php if ($_POST["showalbums"]) echo " checked" ?>
        >
        <label for="showalbums">
          <?php $cameralife->Theme->Image('small-album') ?>Albums
        </label>
      <td width="25%">
        <input type="checkbox" id="showusers" name="showusers"
          <?php if ($_POST["showusers"]) echo " checked" ?>
        >
        <label for="showusers">
          <?php $cameralife->Theme->Image('small-login') ?>Users
        </label>
      <td width="25%">
        <input type="checkbox" id="showpreferences" name="showpreferences"
          <?php if ($_POST["showpreferences"]) echo " checked" ?>
        >
        <label for="showpreferences">
          <?php $cameralife->Theme->Image('small-admin') ?>Preferences
        </label>
  </table>
  </div>
  <div id="allusers" <?= ($showallusers) ? '' : 'style="display:none"' ?>>
  <h2>Show changes by anyone <a href="#" onclick="toggleUsers('someusers','allusers')">[change]</a></h2>
  </div>
  <div id="someusers" <?= ($showallusers) ? 'style="display:none"' : '' ?>>
  <h2>Show changes by these users <a href="#" onclick="toggleUsers('allusers','someusers')">[show all]</a></h2>
  <table width="100%">
    <tr>
      <td width="33%">
        <input type="checkbox" id="showme" name="showme"
          <?php if ($_POST["showme"]) echo " checked" ?>
        >
        <label for="showme">
          <?php $cameralife->Theme->Image('small-login') ?>Me
        </label>
      <td width="33%">
        <input type="checkbox" id="showreg" name="showreg"
          <?php if ($_POST["showreg"]) echo " checked" ?>
        >
        <label for="showreg">
          <?php $cameralife->Theme->Image('small-login') ?>Other Registered Users
        </label>
      <td width="33%">
        <input type="checkbox" id="showunreg" name="showunreg"
          <?php if ($_POST["showunreg"]) echo " checked" ?>
        >
        <label for="showunreg">
          <?php $cameralife->Theme->Image('small-login') ?>Unregistered Users
        </label>
  </table>
  </div>
  <h2>Show changes since <span style="color: green">last checkpoint</span></h2>
  <!--
    <select>
      <option>Last checkpoint</option>
      <option>A week ago</option>
      <option>A month ago</option>
      <option>The last 100 changes</option>
    </select>
  -->
  <p><input type=submit value="Query logs"></p>

  <table align="center" cellspacing="2" border=1 width="100%">
    <tr>
      <th colspan=2>Results
  <?php
    $condition = "(0 ";
    if ($_POST['showphotos']) 
      $condition .= "OR record_type = 'photo' ";
    if ($_POST['showalbums'])
      $condition .= "OR record_type = 'album' ";
    if ($_POST['showusers'])
      $condition .= "OR record_type = 'user' ";
    if ($_POST['showpreferences'])
      $condition .= "OR record_type = 'preference' ";

    $condition .= ") AND (0 ";
    if ($_POST['showme']) 
      $condition .= "OR user_name = '".$cameralife->Security->GetName()."' ";
    if ($_POST['showreg']) 
      $condition .= "OR (user_name LIKE '_%' AND user_name != '".$cameralife->Security->GetName()."')";
    if ($_POST['showunreg']) 
      $condition .= "OR user_name = '' ";
    $condition .= ") ";

    $checkpoint = $cameralife->Database->SelectOne('logs','MAX(id)');
    echo "<input type='hidden' name='checkpoint' value='$checkpoint'>\n";

    $condition .= " AND id > ".($cameralife->preferences['core']['checkpoint']+0);
    $extra = "GROUP BY record_id, record_type, value_field ORDER BY id DESC";

    $result = $cameralife->Database->Select('logs','*, MAX(id) as maxid',$condition,$extra);
    while($record = $result->FetchAssoc())
    {
      echo "<tr><td align=center>";
      if ($record['record_type'] == 'photo')
      {
        echo "<a href=\"../photo.php&#63;id=".$record['record_id']."\">";
        $cameralife->Theme->Image('small-photo');
        echo "</a>";
      }
      else if ($record['record_type'] == 'album')
        $cameralife->Theme->Image('small-album');
      else if ($record['record_type'] == 'preference')
        $cameralife->Theme->Image('small-admin');
      else if ($record['record_type'] == 'user')
        $cameralife->Theme->Image('small-user');
      echo "<br><i>".$record['value_field']."</i>";
      echo "<td>\n";

      $condition = "record_id = ".$record['record_id']."
                    AND record_type = '".$record['record_type']."'
                    AND value_field = '".$record['value_field']."'";
      $result2 = $cameralife->Database->Select('logs','*',$condition, 'ORDER BY id DESC');

      unset($last_row);
      while ($row = $result2->FetchAssoc())
      {
        $checked = $last_row ? '' : 'checked';
        echo "<input id=\"".(++$htmlid)."\" type=\"radio\" $checked name=\"".$record['maxid']."\" value=\"".$last_row['id']."\"> ";
        echo "<label style=\"color: brown\" for=\"$htmlid\">\"".$row['value_new']."\"</label> ";
        echo ($row['user_name']?$row['user_name']:'Anonymous').' ('.$row['user_ip'].') '.$row['user_date']."\n";
        echo "<br>";
        $last_row = $row;
      }

      echo "<span style='color:green'>";
      echo "<input id=\"".(++$htmlid)."\" type=radio name=\"".$record['maxid']."\" value=\"".$last_row['id']."\"> ";
      echo "<label for=\"$htmlid\">\"".$last_row['value_old']."\"</label>";
      echo "</span>\n\n";
    }
  ?>
  </table>

  <p>
        <input type=submit name="action" value="Commit Changes">
        <a href="logs.php">(Revert to last saved)</a><br>
        <input type=submit name="action" value="Set Checkpoint">
        (do this after committing any changes)
  </p>
  </form>
</body>
</html>


