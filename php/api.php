<?php
session_start();
/*
    Safely control your toon thermostat with a secure connection.

    MADE BY: Sander Jonk 2018
    GITHUB: https://www.github.com/jonkootje
    BUGS: sander@web-sj.com
    WEBSITE: https://www.web-sj.com
*/


// SETTINGS - CHANGE THESE \/\/\/\/\/\/\/\/\/

$PASSWORD = 'password'; // PASSWORD TO GET ACCESS TO TOON CONTROLLS   
$PASSWORD_VERSION = 1; // INCREASE WHEN NEW PASSWORD (ALSO CHANGE IN INDEX.PHP)
$ADRESS = '192.168.0.45'; // LOCAL IP ADRESS OF TOON SERVER
$VERSION = '4.8'; // TOON VERSION (SUPPORTED: 4.8 / 4.9)

// END SETTINGS /\/\/\/\/\/\/\/\



function buildUrl($adress, $get, $type = 'thermostat') {
    global $VERSION;

    if ($type == 'thermostat') {
        if ($VERSION == "4.9") {
            return 'http://'. $adress .'/happ_thermstat?'. $get;
        } else {
            return 'http://'. $adress .'/happ_thermostat?'. $get;
        }   
    } elseif ($type == 'power') {
        return 'http://'. $adress .'/happ_pwrusage?'. $get;
        // action=GetCurrentUsage 
    }
}

function getData($url) {
    return json_decode(file_get_contents($url));
}

function isRemembered() {
    if (isset($_COOKIE['toon_remember'])) {
        $rememberedData = unserialize(file_get_contents('../data/remember.dat'));
        if (!is_array($rememberedData)) {
            $rememberedData = array();
        }
        $rememberId = $_COOKIE['toon_remember'];
        if (isset($rememberedData[$rememberId]) && $rememberedData[$rememberId] > $PASSWORD_VERSION) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function random_str($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function remember() {
    global $PASSWORD_VERSION;
    $hash = sha1(random_str(16) . (string) microtime());
    $rememberedData = unserialize(file_get_contents('../data/remember.dat'));
    if (!is_array($rememberedData)) {
        $rememberedData = array();
    }
    $rememberedData[$hash] = $PASSWORD_VERSION;
    file_put_contents('../data/remember.dat', serialize($rememberedData));

    setcookie(
        "toon_remember",
        $hash,
        time() + (10 * 365 * 24 * 60 * 60),
        "/"
    );
    return $hash;
}

function isLoggedin() {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        return true;
    } else {
        if (isRemembered()) {
            $_SESSION['loggedin'] = true;
        } else {
            return false;
        }
    }
}

$output = array(
    "success"=>false,
    "error"=>"UNKNOWN"
);

if (!isset($_POST['command'])) {
    $output['error'] = 'MISSING_COMMAND';
} else {
    // Handle commands

    $command = strtoupper($_POST['command']);

    if ($command == 'LOGIN') {
        if (!isset($_POST['password']) || $_POST['password'] !== $PASSWORD) {
            // Invalid password
            $_SESSION['loggedin'] = false;
            $output['error'] = 'INVALID_LOGIN';
        } else {
            // Valid password
            $_SESSION['loggedin'] = true;
            $output['success'] = true;
            $output['error'] = 'LOGGEDIN';
            remember();
        }
    } elseif ($command == 'APP') {
        
        if (!isLoggedin()) {
            $output['error'] = 'NO_PERMISSION';
        } else {
            // Parse to toon
            $url = buildUrl($ADRESS, $_GET);
            $data = getData($url);
            $output['return'] = $data;
            $output['success'] = true;
            $output['error'] = 'NONE';
        }
    } elseif ($command == 'GETDATA') {
            // Ask toon for status information (Power and Thermo)
            $url = buildUrl($ADRESS, 'action=getThermostatInfo');

            $dataThermo = getData($url);
            $url = buildUrl($ADRESS, 'action=GetCurrentUsage', 'power');
            $dataPower = getData($url);
            $data = array_merge((array)$dataThermo, (array)$dataPower);

            $output['return'] = $data;
            $output['success'] = true;
            $output['error'] = 'NONE';
    } else {
        $output['error'] = 'INVALID_COMMAND';
    }
}

echo json_encode($output);


?>
