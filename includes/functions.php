<?php

function icon(string $icon, int $rem = 2, string $color = 'cornflowerblue') {
    return '<i class="bi bi-'.$icon.'" style="font-size: '.$rem.'rem; color: '.$color.';"></i>';
}

function cipherLen(string $method = ENC_METHOD) {
    return openssl_cipher_iv_length($method);
}

function genIV($method = ENC_METHOD) {
    $len   = cipherLen($method);
    $bytes = openssl_random_pseudo_bytes($len);
    return bin2hex($bytes);
}

function encrypt($s, $p, $iv = "") {
    if (!empty($iv) && ctype_xdigit($iv)) {
        $iv = hex2bin($iv);
    }

    $ivlen = strlen($iv);
    if ($ivlen !== 0 && $ivlen !== cipherLen()) {
        die("Invalid IV length (".strlen($iv)."). Expected ".cipherLen());
    }

    $encrypted = openssl_encrypt($s, ENC_METHOD, $p, iv: $iv);
    return $encrypted;
}

function decrypt($s, $p, $iv = "") {
    if (!empty($iv) && ctype_xdigit($iv)) {
        $iv = hex2bin($iv);
    }

    $ivlen = strlen($iv);
    if ($ivlen !== 0 && $ivlen !== cipherLen()) {
        die("Invalid IV length (".strlen($iv)."). Expected ".cipherLen());
    }

    $decrypted = openssl_decrypt($s, ENC_METHOD, $p, iv: $iv);
    return $decrypted;
}

# passGen(15, "lud")
function passGen($l = 15, $t = 'lud') {
    try {
        $lc = strpos($t, "l");
        $uc = strpos($t, "u");
        $di = strpos($t, "d");
        $sy = strpos($t, "s");
    
        $alphabet = "";
        if ($lc !== false) {
            $alphabet .= "abcdefghijklmnopqrstuvwxyz";
        }
        if ($uc !== false) {
            $alphabet .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        if ($di !== false) {
            $alphabet .= "0123456789";
        }
        if ($sy !== false) {
            $alphabet .= "!#%-_.,=?";
        }
        $alen = strlen($alphabet);
        $str = "";
    
        for ($i = 0; $i < $l; $i++) {
            $r = mt_rand(0, $alen-1);
            str_split($alphabet);
            $str .= $alphabet[$r];
        }
        return $str;
    } catch (Throwable $th) {
        return "Exception in passGen(): $th";
    }
}

function setup_error(string $text, int $setstatuscode = 0) {
    global $status;
    global $error;

    if ($setstatuscode != 0) {
        $status = $setstatuscode;
    }

    array_push($error, $text);
    return $error;
}

function setup_info(string $text, $type = "info") {
    global $info;
    array_push($info, alert($text, $type));
    return $info;
}

function alert($txt, $type = 'info', $icon = '') {
    if ($type == 'info') {
        $icon = 'ℹ️';
    }
    if ($type == 'danger') {
        $icon = '❌';
    }
    if ($type == 'warning') {
        $icon = '⚠️';
    }
    if ($type == 'success') {
        $icon = '✅';
    }

    $txt = $icon.' '.$txt;

    return '
    <div class="alert alert-'.$type.'">'.$txt.'</div>
    ';
}