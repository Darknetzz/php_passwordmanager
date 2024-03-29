<?php
session_start();
error_reporting(E_ALL);

define("CONFIG_FILE", "includes/config.php");
define("SETUP_FILE", "includes/setup.php");
define("FUNCTIONS_FILE", "includes/functions.php");
define("BOOTSTRAP_FILE", 'includes/bootstrap.php');
define("MASTER_PASSWORD_MINLEN", 8);

require_once(FUNCTIONS_FILE);
?>

<?php

/* ───────────────────────────────────────────────────────────────────── */
/*                         Warn user about HTTPS                         */
/* ───────────────────────────────────────────────────────────────────── */
if (isSecure() !== True) {
  echo alert("
  Warning
  <hr>
  It is highly recommended you switch to using HTTPS for two main reasons:
  <ul>
    <li>When using HTTPS, your traffic is encrypted and can't be monitored on local networks</li>
    <li>You will be able to use the copy to clipboard function</li>
  </ul>
  <i>To ignore this warning, set <code>IGNORE_SSL_WARNING</code> in your <b>config.php</b> file to <code>True</code></i>
    ", "danger");
}

try {
  /* ────────────────────────────────────────────────────────────────────────── */
  /*            Verify that config exists, if not, require setup.php            */
  /* ────────────────────────────────────────────────────────────────────────── */
  if (!file_exists(CONFIG_FILE)) {
    die(require_once(SETUP_FILE));
  }
  
  if (empty(file_get_contents(CONFIG_FILE))) {
    die(require_once(SETUP_FILE));
  }
  
  /* ────────────────────────────────────────────────────────────────────────── */
  /*                          All good, config exists!                          */
  /* ────────────────────────────────────────────────────────────────────────── */
  require_once(CONFIG_FILE);
  require_once(BOOTSTRAP_FILE);
  
  $title = "PHP Password Manager";
  if (defined("SITE_TITLE")) {
    $title = SITE_TITLE;
  }
  
  echo "<title>$title</title>";
} catch (Throwable $t) {
    echo alert("Setup couldn't run: $t");
}

?>

<?php 
  if (!defined("BACKGROUND_COLOR")) {
    define("BACKGROUND_COLOR", "#111");
  }
  echo "<body>";
?>
<div class="container" style="padding-top:10px;">
<?php
if (isset($_POST['mpassword'])) {
    $_GET['lock'] = null;
    $_SESSION['password'] = $_POST['mpassword'];
}

if (isset($_GET['lock'])) {
    $_SESSION['password'] = null;
    header("Location: index.php");
}

$pwInput = "Please enter master password: 
<form action='' method='POST' autocomplete='off'>
<input type='password' name='mpassword' class='form-control' autocomplete='off' spellcheck='false' autofocus>
</form>";


# User is attempting to sign in, but password is incorrect
if (isset($_POST['mpassword'])) {
  $hpw = hash("sha512", $_POST['mpassword'].SALT);
  if ($hpw <> MASTER_PASSWORD) {
    echo alert("Invalid password", "danger");
    die($pwInput);
  }
}

# User is not signed in
if (!isset($_SESSION['password'])) {
    die($pwInput);
}

if (isset($_POST['del'])) {
  $id = mysqli_real_escape_string($sqlcon, $_POST['id']);
  $delete = "DELETE FROM accounts WHERE id = '$id'";
  $delete = mysqli_query($sqlcon, $delete);
  if ($delete) {
    echo "<div class='alert alert-success'>Entry deleted.</div>";
  } else {
    echo "<div class='alert alert-danger'>Could not delete entry: $sqlcon->error</div>";
  }
}

if (isset($_POST['edit'])) {
  $id = mysqli_real_escape_string($sqlcon,$_POST['id']);
  $name = mysqli_real_escape_string($sqlcon,$_POST['name']);
  $username = mysqli_real_escape_string($sqlcon,$_POST['username']);
  $salt = passGen(32, 'lud');
  $iv = genIV();
  $password = encrypt($_POST['password'], $salt.MASTER_PASSWORD, iv: $iv);
  $desc = mysqli_real_escape_string($sqlcon,$_POST['desc']);
  $url = mysqli_real_escape_string($sqlcon,$_POST['url']);
  $tfa = mysqli_real_escape_string($sqlcon, $_POST['2fa']);
  # check if entry exists
  $exists = "SELECT * FROM accounts WHERE `id` = '$id'";
  $exists = mysqli_query($sqlcon, $exists);
  if ($exists->num_rows > 0) {
  $edit = "UPDATE accounts SET `name` = '$name', `username` = '$username', `password` = '$password', `salt` = '$salt', `iv` = '$iv', `url` = '$url', `description` = '$desc', `2fa` = '$tfa' WHERE id = '$id'";
  $edit = mysqli_query($sqlcon, $edit);
  if ($edit) {
    echo "<div class='alert alert-success'>Entry <b>$name</b> updated!</div>";
  } else {
    echo "<div class='alert alert-danger'>Could not update entry: $sqlcon->error</div>";
  }
} else {
  echo "<div class='alert alert-danger'>This entry (ID #$id) does not exist.</div>";
}
}

if (isset($_POST['add'])) {
  $name = mysqli_real_escape_string($sqlcon, $_POST['name']);
  $username = mysqli_real_escape_string($sqlcon, $_POST['username']);
  $salt = passGen(32, 'lud');
  $iv = genIV();
  $password = encrypt($_POST['password'], $salt.MASTER_PASSWORD, iv: $iv);
  $desc = mysqli_real_escape_string($sqlcon, $_POST['desc']);
  $url = mysqli_real_escape_string($sqlcon, $_POST['url']);
  $tfa = mysqli_real_escape_string($sqlcon, $_POST['2fa']);
  $add = "INSERT INTO accounts (`name`, `username`, `password`, `salt`, `iv`, `description`, `url`, `2fa`) VALUES ('$name', '$username', '$password', '$salt', '$iv', '$desc', '$url', '$tfa')";
  $add = mysqli_query($sqlcon, $add);
  if ($add) {
    echo "<div class='alert alert-success'>Entry added.</div>";
  } else {
    echo "<div class='alert alert-danger'>Entry couldn't be added: $sqlcon->error</div>";
  }
}


if (isset($_GET['s'])) {
  $s = mysqli_real_escape_string($sqlcon, $_GET['s']);
  $accounts = "SELECT * FROM accounts WHERE 
              LOWER(`name`) LIKE LOWER('%$s%') OR
              LOWER(`username`) LIKE LOWER('%$s%') OR
              LOWER(`url`) LIKE LOWER('%$s%') OR
              LOWER(`description`) LIKE LOWER('%$s%') ORDER BY id DESC";
} else {
  $accounts = "SELECT * FROM accounts ORDER BY id DESC";
}
$accounts = mysqli_query($sqlcon, $accounts);
?>
<form action="" method="GET">
<div class="input-group">
<div class="input-group-prepend">
  <span class="input-group-text" id="basic-addon1"><?= icon("search") ?></span>
</div>
  <input type="text" name="s" class="form-control" placeholder="Search" autofocus>
</div>
</form>
<!-- Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1" role="dialog" aria-labelledby="addEntryModal" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <!--<span aria-hidden="true">&times;</span>-->
        </button>
      </div>
      <div class="modal-body">
        <form action="" method="POST">
        <input type="hidden" name="add" value="1">
        <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">Name</span>
        </div>
          <input type="text" class="form-control" name="name" placeholder="Name">
        </div>
        <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">Username</span>
        </div>
          <input type="text" class="form-control" name="username" placeholder="Username">
        </div>
        <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">Password</span>
        </div>
          <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="">
          <a href="javascript:void(0);" class="genPass btn btn-primary" data-output="#password">Generate</a>
        </div>
        <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">URL</span>
        </div>
          <input type="text" class="form-control"name="url"  placeholder="URL">
        </div>
        <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="basic-addon1">Description</span>
        </div>
          <input type="text" class="form-control" name="desc" placeholder="Description">
        </div>
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">2FA</span>
          </div>
          <input type="hidden" name="2fa" value="0">
          <div class="form-check form-switch">
            <input name="2fa" value="1" class="form-check-input" type="checkbox">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-primary" value="Create">
        </form>
      </div>
    </div>
  </div>
</div>

<?php
echo "<div class='btn-group'>";
echo "<a class='btn btn-primary' href='index.php'>".icon('house-door-fill', color: 'white')."</a> ";
echo "<a class='btn btn-primary' href='#' data-bs-toggle='modal' data-bs-target='#settingsModal'>".icon('gear-fill', color: 'white')."</a> ";
echo "<a class='btn btn-success' href='#' data-bs-toggle='modal' data-bs-target='#addEntryModal'>".icon('plus-circle-fill', color: 'white')."</a> ";
echo "<a class='btn btn-danger' href='?lock=1'>".icon('lock-fill', color: 'white')."</a> ";
echo "</div>";

echo "<hr>";
echo "<div id='errors'></div>"; # for outputting errors from javascript

if ($accounts->num_rows < 1) {
  die(alert("Nothing added yet!"));
}
$iteration = 1;
while ($account = $accounts->fetch_assoc()) {
  $checked = null;
  if ($account['2fa'] == 1) {
    $checked = "checked";
  }
  $iv = null;
  if (!empty($account['iv'])) {
    $iv = hex2bin($account['iv']);
  }
  # EDIT ENTRY
  $salt = null;
  if (!empty($account['salt'])) {
    $salt = $account['salt'];
  }
  $decryptedPass = decrypt($account['password'], $salt.MASTER_PASSWORD, $iv);
  echo '
  <!-- Modal -->
  <div class="modal fade" id="editEntryModal'.$account['id'].'" tabindex="-1" role="dialog" aria-labelledby="editEntryModal'.$account['id'].'" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <!--<span aria-hidden="true">&times;</span>-->
          </button>
        </div>
        <div class="modal-body">
          <form action="" method="POST">
          <input type="hidden" name="edit" value="1">
          <input type="hidden" name="id" value="'.$account['id'].'">
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">Name</span>
          </div>
            <input type="text" name="name" class="form-control" placeholder="Name" value="'.$account['name'].'">
          </div>
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">Username</span>
          </div>
            <input type="text" name="username" class="form-control" placeholder="Username" value="'.$account['username'].'">
          </div>
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">Password</span>
          </div>
            <input type="password" name="password" class="form-control" placeholder="Password" value="'.$decryptedPass.'">

          </div>
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">URL</span>
          </div>
            <input type="text" name="url" class="form-control" placeholder="URL" value="'.$account['url'].'">
          </div>
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">Description</span>
          </div>
            <input type="text" name="desc" class="form-control" placeholder="Description" value="'.$account['description'].'">
          </div>
          <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text">2FA</span>
          </div>
          <input type="hidden" name="2fa" value="0">
          <div class="form-check form-switch">
            <input name="2fa" value="1" class="form-check-input" type="checkbox" '.$checked.'>
          </div>
        </div>
      </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <input type="submit" class="btn btn-primary" value="Save">
          </form>
        </div>
      </div>
    </div>
  </div>
    <!-- Modal -->
    <div class="modal fade" id="delEntryModal'.$account['id'].'" tabindex="-1" role="dialog" aria-labelledby="delEntryModal'.$account['id'].'" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete entry</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <!--<span aria-hidden="true">&times;</span>-->
            </button>
          </div>
          <div class="modal-body">
            <form action="" method="POST">
            <input type="hidden" name="del" value="1">
            <input type="hidden" name="id" value="'.$account['id'].'">
            <div class="alert alert-warning">Are you sure you want to delete entry '.$account['name'].'?</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <input type="submit" class="btn btn-danger" value="Delete">
            </form>
          </div>
        </div>
      </div>
    </div>
  ';
        if ($iteration == 1) {         
          echo "<table class='table table-hover table-dark' style='table-layout:fixed;'>
          <tr><th>Name</th><th>Username</th><th>Password</th><th>URL</th><th>Description</th><th>2FA</th></tr>";
        }
    $id = 0;
    echo "<tr>";
    for ($i=0;$i<6;$i++) {
    echo "<td>";
    if ($i == 3) {
        # URL Field
        
        echo "
        <a href='javascript:void(0);' onClick='copyTC(\"$account[id]$i$id\");'>".icon('clipboard-fill')."</a>
        <span id='$account[id]$i$id'><a href='$account[url]' target='_blank'>$account[url]</a></span>";
    } elseif ($i == 2) {
        # Password field
        echo "
        <a onClick='copyTC(\"$account[id]$i$id\");'>".icon('clipboard-fill')."</a>
        <a id='$account[id]$i$id-eye' onClick='reveal(\"$account[id]$i$id\");'>".icon('eye')."</a>
        <span id='$account[id]$i$id' style='display:none;font-size:11px;'>".$decryptedPass."</span>
        <span id='$account[id]$i$id-h' style='font-size:11px;'>****************</span>";
    } elseif ($i == 4) {
        # Description field, no need for copy
        echo $account["description"];
    } elseif ($i == 0) {
        # Name field, no need for copy
        echo "<a href='#' data-bs-toggle='modal' data-bs-target='#editEntryModal$account[id]'>".icon('pencil-square')."</a>
              <a href='#' data-bs-toggle='modal' data-bs-target='#delEntryModal$account[id]'>".icon('trash3-fill', color: 'red')."</a>
              ".$account["name"];
    } elseif ($i == 5) {
        # 2FA Field
        $tfa = $account['2fa'];
        if ($tfa == 0) {
          echo icon("ban", color: 'red');
        } else {
          echo icon("check-lg", color: 'green'); 
        }
    } elseif ($i == 1) {
        # Not password field
        echo "<a onClick='copyTC(\"$account[id]$i$id\");'>".icon('clipboard-fill')."</a> <span id='$account[id]$i$id'>$account[username]</span>";
    }
    echo "</td>";
    }
    echo "</tr>";
    $id++;
    $iteration++;
}
echo "</table>";
?>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="liveToast" class="toast bg-text-white bg-info hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <img src="img/ctc.png" class="rounded me-2" alt="Copied" style="width:30px;">
      <strong class="me-auto">Copied!</strong>
      <small>now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      Copied to clipboard!
    </div>
  </div>
</div>
</body>