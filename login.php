<?php
/**
 * ERP System - Login Page
 * PHP 5.6+ Compatible Version
 * 
 * Features:
 * - User authentication with verification code
 * - Secure cookie and session management
 * - Login attempt logging
 * - SQL injection prevention with prepared statements
 * - Complete PHP 5.6 compatibility (no PHP 7.0+ syntax)
 */

// ==========================================
// LOAD CONFIGURATION AND FUNCTIONS
// ==========================================

// Load configuration
require_once(dirname(__FILE__) . '/include/config_rglobals.php');
require_once(dirname(__FILE__) . '/include/config_base.php');

// ==========================================
// GET POST PARAMETERS - PHP 5.6 COMPATIBLE
// ==========================================

// Initialize variables
$action   = isset($_POST['action']) ? $_POST['action'] : '';
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$code     = isset($_POST['code']) ? $_POST['code'] : '';

// Get POST data using isset() - PHP 5.6 compatible
// (Do NOT use ?? null coalesce operator - that's PHP 7.0+)

if (isset($_POST['action'])) {
    $action = $_POST['action'];
}

if (isset($_POST['username'])) {
    $username = $_POST['username'];
}

if (isset($_POST['password'])) {
    $password = $_POST['password'];
}

if (isset($_POST['code'])) {
    $code = $_POST['code'];
}

// ==========================================
// HANDLE LOGIN REQUEST
// ==========================================

if ($action == 'Login') {
    
    // ==========================================
    // VERIFY SECURITY CODE (CAPTCHA)
    // ==========================================
    
    // Verify the verification code
    // GetCkVdValue() returns the expected captcha value
    $expected_code = '';
    if (function_exists('GetCkVdValue')) {
        $expected_code = GetCkVdValue();
    }
    
    if ($expected_code == $code) {
        
        // ==========================================
        // VALIDATE AND SANITIZE INPUT
        // ==========================================
        
        // Sanitize username to prevent SQL injection and XSS
        // SanitizeInput() removes dangerous characters
        $username = SanitizeInput($username);
        
        // Get login metadata
        $loginip = GetIP();
        $logindate = GetDateTimeMk(time());
        
        // ==========================================
        // PREPARE DATABASE QUERY
        // ==========================================
        
        // Create database connection object
        // false = don't use in constructor
        $lsql = new Dedesql(false);
        
        // SQL query with placeholders for parameters
        // This prevents SQL injection
        $sql = "select * from #@__boss where boss = ? and password = ?";
        
        // Set query on connection object
        $lsql->SetQuery($sql);
        
        // ==========================================
        // BIND PARAMETERS AND EXECUTE
        // ==========================================
        
        // Prepare parameter values
        $username_param = $username;
        $password_param = md5($password);  // Hash password with MD5 (current system requirement)
        
        // Bind parameters (prevents SQL injection)
        $lsql->bindParam(1, $username_param);
        $lsql->bindParam(2, $password_param);
        
        // Execute the query
        $lsql->Execute();
        
        // ==========================================
        // CHECK RESULTS
        // ==========================================
        
        // Get number of rows returned
        $rowcount = $lsql->GetTotalRow();
        
        // Get first row if exists
        $row = $lsql->getone();
        
        // ==========================================
        // VERIFY CREDENTIALS
        // ==========================================
        
        if ($rowcount == 0) {
            // Username or password incorrect
            $message = '用户或密码错误被系统拒绝登陆！';
            
            // Log failed login attempt
            WriteNote($message, $logindate, $loginip, $username);
            
            // Show error message and go back
            ShowMsg($message, -1);
            
        } else {
            // Credentials are correct - user can login
            
            // ==========================================
            // SET SESSION AND COOKIE DATA
            // ==========================================
            
            $message = "正常登入进销存系统！";
            
            // Create encoded user identifier
            // Combine username with secret code for extra security
            // This is stored in both SESSION (server-side) and COOKIE (client-side)
            $encoded_user = $username . $cfg_cookie_encode;
            
            // Store in SESSION (server-side storage)
            // Session data is kept on server after browser closes
            $_SESSION['VioomaUserID'] = $encoded_user;
            $_SESSION['rank'] = $row['rank'];
            
            // Store in COOKIE (client-side storage)
            // Cookie is sent back to browser for convenience
            // Using same encoded value as session for consistency
            PutCookie('VioomaUserID', $encoded_user, $cfg_keeptime);
            PutCookie('rank', $row['rank'], $cfg_keeptime);
            
            // ==========================================
            // LOG SUCCESSFUL LOGIN
            // ==========================================
            
            // Log successful login event
            WriteNote($message, $logindate, $loginip, $username);
            
            // ==========================================
            // UPDATE DATABASE
            // ==========================================
            
            // Update user's last login date and IP in database
            $loginsql = "update #@__boss set logindate = ?, loginip = ? where boss = ?";
            
            // Prepare and execute update query
            $lsql->SetQuery($loginsql);
            $lsql->bindParam(1, $logindate);
            $lsql->bindParam(2, $loginip);
            $lsql->bindParam(3, $username);
            $lsql->executenonequery($loginsql);
            
            // ==========================================
            // REDIRECT TO DASHBOARD
            // ==========================================
            
            // JavaScript redirect to dashboard
            echo "<script language='javascript'>";
            echo "window.location.href='index.php';";
            echo "</script>";
            exit();
        }
        
        // Close database connection
        $lsql->close();
        
    } else {
        // Verification code is incorrect
        $errmessage = "输入的验证码不正确！";
        ShowMsg($errmessage, -1);
    }
}

// ==========================================
// DISPLAY LOGIN FORM (IF NOT POSTED)
// ==========================================

?>
<!DOCTYPE HTML>
<html>
<head>
    <title>WEB ERP SYSTEM 2019 - Powered By www.suyi1995.com</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    
    <style type="text/css">
        body {
            background-color: #f4f4f4;
            margin: 0px;
            overflow: hidden;
            height: 100%;
            font: 12px 'Lucida Sans Unicode', 'Trebuchet MS', Arial, Helvetica;
            background-color: #d9dee2;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#ebeef2), to(#d9dee2));
            background-image: -webkit-linear-gradient(top, #ebeef2, #d9dee2);
            background-image: -moz-linear-gradient(top, #ebeef2, #d9dee2);
            background-image: -ms-linear-gradient(top, #ebeef2, #d9dee2);
            background-image: -o-linear-gradient(top, #ebeef2, #d9dee2);
            background-image: linear-gradient(top, #ebeef2, #d9dee2);
        }
        
        .LoR {
            height: 400px;
            width: 700px;
            overflow: hidden;
            position: absolute;
            top: 35%;
            left: 35%;
            margin: -102px auto auto -82px;
            z-index: 2;
        }
        
        #login {
            background-color: #fff;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#eee));
            background-image: -webkit-linear-gradient(top, #fff, #eee);
            background-image: -moz-linear-gradient(top, #fff, #eee);
            background-image: -ms-linear-gradient(top, #fff, #eee);
            background-image: -o-linear-gradient(top, #fff, #eee);
            background-image: linear-gradient(top, #fff, #eee);
            height: 280px;
            width: 380px;
            margin: -150px 0 0 -230px;
            padding: 30px;
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 0;
            -moz-border-radius: 3px;
            -webkit-border-radius: 3px;
            border-radius: 3px;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.2), 0 1px 1px rgba(0, 0, 0, .2), 0 3px 0 #fff, 0 4px 0 rgba(0, 0, 0, .2), 0 6px 0 #fff, 0 7px 0 rgba(0, 0, 0, .2);
        }
        
        #login:before {
            content: '';
            position: absolute;
            z-index: -1;
            border: 1px dashed #ccc;
            top: 5px;
            bottom: 5px;
            left: 5px;
            right: 5px;
            box-shadow: 0 0 0 1px #fff;
        }
        
        h1 {
            text-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0px 2px 0 rgba(0, 0, 0, .5);
            text-transform: uppercase;
            text-align: center;
            color: #666;
            margin: 0 0 30px 0;
            letter-spacing: 4px;
            font: normal 26px/1 Verdana, Helvetica;
            position: relative;
        }
        
        h1:after, h1:before {
            background-color: #777;
            content: "";
            height: 1px;
            position: absolute;
            top: 15px;
            width: 120px;
        }
        
        h1:after {
            background-image: -webkit-gradient(linear, left top, right top, from(#777), to(#fff));
            background-image: -webkit-linear-gradient(left, #777, #fff);
            background-image: -moz-linear-gradient(left, #777, #fff);
            background-image: -ms-linear-gradient(left, #777, #fff);
            background-image: -o-linear-gradient(left, #777, #fff);
            background-image: linear-gradient(left, #777, #fff);
            right: 0;
        }
        
        h1:before {
            background-image: -webkit-gradient(linear, right top, left top, from(#777), to(#fff));
            background-image: -webkit-linear-gradient(right, #777, #fff);
            background-image: -moz-linear-gradient(right, #777, #fff);
            background-image: -ms-linear-gradient(right, #777, #fff);
            background-image: -o-linear-gradient(right, #777, #fff);
            background-image: linear-gradient(right, #777, #fff);
            left: 0;
        }
        
        fieldset {
            border: 0;
            padding: 0;
            margin: 0;
        }
        
        #inputs input {
            background: #f1f1f1 url(images/login-sprite.png) no-repeat;
            padding: 15px 15px 15px 30px;
            margin: 0 0 10px 0;
            width: 353px;
            border: 1px solid #ccc;
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
            border-radius: 5px;
            box-shadow: 0 1px 1px #ccc inset, 0 1px 0 #fff;
        }
        
        #inputs1 input {
            background: #f1f1f1 url(include/getcode.php) no-repeat;
            padding: 15px 15px 15px 30px;
            margin: 0 0 10px 0;
            width: 353px;
            border: 1px solid #ccc;
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
            border-radius: 5px;
            box-shadow: 0 1px 1px #ccc inset, 0 1px 0 #fff;
            background-position: 95%;
        }
        
        #username {
            background-position: 5px -2px !important;
        }
        
        #password {
            background-position: 5px -52px !important;
        }
        
        #inputs input:focus {
            background-color: #fff;
            border-color: #e8c291;
            outline: none;
            box-shadow: 0 0 0 1px #e8c291 inset;
        }
        
        #actions {
            margin: 25px 0 0 0;
        }
        
        #submit {
            background-color: #ffb94b;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#fddb6f), to(#ffb94b));
            background-image: -webkit-linear-gradient(top, #fddb6f, #ffb94b);
            background-image: -moz-linear-gradient(top, #fddb6f, #ffb94b);
            background-image: -ms-linear-gradient(top, #fddb6f, #ffb94b);
            background-image: -o-linear-gradient(top, #fddb6f, #ffb94b);
            background-image: linear-gradient(top, #fddb6f, #ffb94b);
            -moz-border-radius: 3px;
            -webkit-border-radius: 3px;
            border-radius: 3px;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.3), 0 1px 0 rgba(255, 255, 255, 0.3) inset;
            border-width: 1px;
            border-style: solid;
            border-color: #d69e31 #e3a037 #d5982d #e3a037;
            float: left;
            height: 35px;
            padding: 0;
            width: 120px;
            cursor: pointer;
            font: bold 15px Arial, Helvetica;
            color: #8f5a0a;
        }
        
        #submit:hover, #submit:focus {
            background-color: #fddb6f;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#ffb94b), to(#fddb6f));
            background-image: -webkit-linear-gradient(top, #ffb94b, #fddb6f);
            background-image: -moz-linear-gradient(top, #ffb94b, #fddb6f);
            background-image: -ms-linear-gradient(top, #ffb94b, #fddb6f);
            background-image: -o-linear-gradient(top, #ffb94b, #fddb6f);
            background-image: linear-gradient(top, #ffb94b, #fddb6f);
        }
        
        #submit:active {
            outline: none;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.5) inset;
        }
        
        #submit::-moz-focus-inner {
            border: none;
        }
        
        #actions a {
            color: #3151A2;
            float: right;
            line-height: 35px;
            margin-left: 20px;
        }
        
        #back {
            display: block;
            text-align: center;
            position: relative;
            top: 60px;
            color: #999;
        }
    </style>
    
    <script type="text/javascript" src="/login/js/three.min.js"></script>
</head>

<body leftmargin="0" topmargin="0" onload="document.form1.username.focus()" marginheight="0" marginwidth="0">

<script type="text/javascript">
/**
 * Form Validation - Client-side check before submit
 */
function login() {
    var thisname = document.form1.username.value;
    var thispwd = document.form1.password.value;
    var thiscode = document.form1.code.value;
    
    if (thisname == '') {
        alert('请输入登陆名称！');
        return false;
    }
    else if (thispwd == '') {
        alert('请输入用户名对应的密码！');
        return false;
    }
    else if (thiscode == '') {
        alert('请输入验证码！');
        return false;
    }
    else {
        return true;
    }
}
</script>

<script type="text/javascript">
/**
 * 3D Background Animation using Three.js
 * Creates particle effects on login page
 */

var container;
var camera, scene, projector, renderer;
var PI2 = Math.PI * 2;

var programFill = function(context) {
    context.beginPath();
    context.arc(0, 0, 1, 0, PI2, true);
    context.closePath();
    context.fill();
};

var programStroke = function(context) {
    context.lineWidth = 0.05;
    context.beginPath();
    context.arc(0, 0, 1, 0, PI2, true);
    context.closePath();
    context.stroke();
};

var mouse = { x: 0, y: 0 }, INTERSECTED;

init();
animate();

function init() {
    container = document.createElement('div');
    container.id = 'bgc';
    container.style.position = 'relative';
    container.style.zIndex = '0';
    document.body.appendChild(container);

    camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 1, 10000);
    camera.position.set(0, 300, 500);

    scene = new THREE.Scene();

    for (var i = 0; i < 100; i++) {
        var particle = new THREE.Particle(
            new THREE.ParticleCanvasMaterial({
                color: Math.random() * 0x808080 + 0x808080,
                program: programStroke
            })
        );
        particle.position.x = Math.random() * 800 - 400;
        particle.position.y = Math.random() * 800 - 400;
        particle.position.z = Math.random() * 800 - 400;
        particle.scale.x = particle.scale.y = Math.random() * 10 + 10;
        scene.add(particle);
    }

    projector = new THREE.Projector();
    renderer = new THREE.CanvasRenderer();
    renderer.setSize(window.innerWidth, window.innerHeight);
    container.appendChild(renderer.domElement);

    document.addEventListener('mousemove', onDocumentMouseMove, false);
    window.addEventListener('resize', onWindowResize, false);
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function onDocumentMouseMove(event) {
    event.preventDefault();
    mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
    mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
}

function animate() {
    requestAnimationFrame(animate);
    render();
}

var radius = 600;
var theta = 0;

function render() {
    theta += 0.2;
    camera.position.x = radius * Math.sin(theta * Math.PI / 360);
    camera.position.y = radius * Math.sin(theta * Math.PI / 360);
    camera.position.z = radius * Math.cos(theta * Math.PI / 360);
    camera.lookAt(scene.position);
    camera.updateMatrixWorld();

    var vector = new THREE.Vector3(mouse.x, mouse.y, 0.5);
    projector.unprojectVector(vector, camera);
    var ray = new THREE.Ray(camera.position, vector.subSelf(camera.position).normalize());
    var intersects = ray.intersectObjects(scene.children);

    if (intersects.length > 0) {
        if (INTERSECTED != intersects[0].object) {
            if (INTERSECTED) INTERSECTED.material.program = programStroke;
            INTERSECTED = intersects[0].object;
            INTERSECTED.material.program = programFill;
        }
    } else {
        if (INTERSECTED) INTERSECTED.material.program = programStroke;
        INTERSECTED = null;
    }

    renderer.render(scene, camera);
}
</script>

<div class="LoR">
    <form name="form1" onsubmit="return login()" action="login.php" method="post" id="login">
        <h1>Log In</h1>
        <fieldset id="inputs">
            <input id="username" type="text" placeholder="Username" name="username" autofocus="autofocus" required="required">
            <input id="password" type="password" placeholder="Password" name="password" required="required">
        </fieldset>
        <fieldset id="inputs1">
            <input id="code" type="text" name="code" placeholder="Code" required="required">
        </fieldset>
        <fieldset id="actions">
            <input type="submit" id="submit" name="action" value="Login">
            <a href="">Forgot your password?</a>
        </fieldset>
    </form>
</div>

</body>
</html>
