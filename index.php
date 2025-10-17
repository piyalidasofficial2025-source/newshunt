<?php
session_start();

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root"; // change as needed
$password = "";     // change as needed
$dbname = "innerwear_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);

// --- CREATE USERS TABLE IF NOT EXISTS ---
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
)");

// --- HANDLE REGISTER ---
$register_msg = "";
if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $conn->prepare("SELECT * FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $res = $check->get_result();
    if($res->num_rows>0){
        $register_msg = "Email already exists!";
    }else{
        $stmt = $conn->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)");
        $stmt->bind_param("sss",$name,$email,$pass);
        $stmt->execute();
        $register_msg = "Registered successfully! Login below.";
    }
}

// --- HANDLE LOGIN ---
$login_msg = "";
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        $user = $res->fetch_assoc();
        if(password_verify($password,$user['password'])){
            $_SESSION['user'] = $user['name'];
            $_SESSION['cart'] = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
            header("Location: index.php"); exit();
        } else $login_msg="Invalid password!";
    } else $login_msg="No user found!";
}

// --- HANDLE LOGOUT ---
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php"); exit();
}

// --- HANDLE ADD TO CART ---
if(isset($_POST['add_to_cart']) && isset($_SESSION['user'])){
    $pname = $_POST['product_name'];
    $price = floatval($_POST['price']);
    $qty = intval($_POST['quantity']);
    if(isset($_SESSION['cart'][$pname])) $_SESSION['cart'][$pname]['quantity']+=$qty;
    else $_SESSION['cart'][$pname]=['price'=>$price,'quantity'=>$qty];
    header("Location: index.php?page=cart"); exit();
}

// --- HANDLE REMOVE FROM CART ---
if(isset($_GET['remove'])){
    $item = $_GET['remove'];
    unset($_SESSION['cart'][$item]);
    header("Location: index.php?page=cart"); exit();
}

// --- ROUTING ---
$page = $_GET['page'] ?? "home";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Innerwear Store</title>
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#fff0f5; color:#333; }
header { background: linear-gradient(135deg,#ff80ab,#ff4081); color:white; text-align:center; padding:30px; }
header h1 { margin:0; font-size:32px; }
nav { display:flex; justify-content:center; gap:15px; margin-top:10px; }
nav a { color:white; text-decoration:none; border:1px solid white; border-radius:20px; padding:6px 12px; font-weight:bold; }
nav a:hover { background:white; color:#ff4081; }
.container { width:90%; max-width:1000px; margin:30px auto; }
.products { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
.product { background:white; border-radius:15px; padding:15px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.product img { width:100%; height:250px; object-fit:cover; border-radius:10px; }
.product h3 { margin:10px 0 5px; color:#ff4081; }
.product .old-price { text-decoration:line-through; color:#888; margin-right:5px; }
.add-btn { background:#ff4081; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; }
.add-btn:hover { background:#e91e63; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th,td { padding:12px; text-align:center; border-bottom:1px solid #ddd; }
th { background:#ff4081; color:white; }
button { background:#ff4081; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer; }
button:hover { background:#e91e63; }
form input[type=number] { width:50px; text-align:center; }
.message { color:red; text-align:center; }
.success { color:green; text-align:center; }
</style>
</head>
<body>

<header>
<h1>Feel Confident. Feel Beautiful 💖</h1>
<nav>
<a href="index.php">Home</a>
<?php if(isset($_SESSION['user'])): ?>
<a href="index.php?page=cart">Cart (<?=count($_SESSION['cart']??[])?>)</a>
<a href="index.php?page=dashboard">My Account (<?= $_SESSION['user'];?>)</a>
<a href="index.php?logout=1">Logout</a>
<?php else: ?>
<a href="index.php?page=login">Login</a>
<a href="index.php?page=register">Sign Up</a>
<?php endif; ?>
</nav>
</header>

<div class="container">

<?php if($page=="home"): ?>

<section class="products">
<?php
$products = [
    ['name'=>'Silk Comfort Bra','price'=>499,'old'=>799,'img'=>'https://i.imgur.com/l5sK8gZ.jpg'],
    ['name'=>'Soft Lace Panty','price'=>299,'old'=>499,'img'=>'https://i.imgur.com/t0bhs9B.jpg'],
    ['name'=>'Comfy Sleep Shorts','price'=>599,'old'=>899,'img'=>'https://i.imgur.com/2RivHzg.jpg'],
    ['name'=>'Stylish Bra & Panty Set','price'=>799,'old'=>1299,'img'=>'https://i.imgur.com/XtpvPVN.jpg'],
];
foreach($products as $p): ?>
<div class="product">
<img src="<?=$p['img']?>" alt="<?=$p['name']?>">
<h3><?=$p['name']?></h3>
<p><span class="old-price">₹<?=$p['old']?></span> <span class="price">₹<?=$p['price']?></span></p>
<?php if(isset($_SESSION['user'])): ?>
<form method="POST" action="index.php?page=cart">
<input type="hidden" name="product_name" value="<?=$p['name']?>">
<input type="hidden" name="price" value="<?=$p['price']?>">
<input type="number" name="quantity" value="1" min="1">
<button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
</form>
<?php else: ?>
<p><a href="index.php?page=login">Login to Buy</a></p>
<?php endif; ?>
</div>
<?php endforeach; ?>
</section>

<?php elseif($page=="register"): ?>
<h2>Register</h2>
<?php if($register_msg) echo "<p class='message'>$register_msg</p>"; ?>
<form method="POST" style="text-align:center;">
<input type="text" name="name" placeholder="Full Name" required><br><br>
<input type="email" name="email" placeholder="Email" required><br><br>
<input type="password" name="password" placeholder="Password" required><br><br>
<button type="submit" name="register">Sign Up</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="index.php?page=login">Already have account? Login</a></p>

<?php elseif($page=="login"): ?>
<h2>Login</h2>
<?php if($login_msg) echo "<p class='message'>$login_msg</p>"; ?>
<form method="POST" style="text-align:center;">
<input type="email" name="email" placeholder="Email" required><br><br>
<input type="password" name="password" placeholder="Password" required><br><br>
<button type="submit" name="login">Login</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="index.php?page=register">New user? Register</a></p>

<?php elseif($page=="dashboard" && isset($_SESSION['user'])): ?>
<h2>My Account</h2>
<p>Welcome, <?= $_SESSION['user'];?> 👋</p>
<p><a href="index.php?page=cart"><button>View Cart</button></a></p>

<?php elseif($page=="cart" && isset($_SESSION['user'])): ?>
<h2>My Cart</h2>
<?php if(empty($_SESSION['cart'])): ?>
<p>Your cart is empty. <a href="index.php">Shop Now</a></p>
<?php else: ?>
<table>
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Action</th></tr>
<?php
$total=0;
foreach($_SESSION['cart'] as $name=>$d){
    $sub=$d['price']*$d['quantity'];
    $total+=$sub;
    echo "<tr><td>$name</td><td>{$d['price']}</td><td>{$d['quantity']}</td><td>$sub</td><td><a href='index.php?page=cart&remove=".urlencode($name)."'>Remove</a></td></tr>";
}
$delivery=$total<=500?12:24;
$grand=$total+$delivery;
?>
<tr><th colspan=3>Total</th><th colspan=2><?=$total?> + Delivery <?=$delivery?> = <?=$grand?></th></tr>
</table>
<p style="margin-top:10px;"><a href="index.php?page=checkout"><button>Proceed to Checkout</button></a></p>
<?php endif; ?>

<?php elseif($page=="checkout" && isset($_SESSION['user'])): ?>
<h2>Checkout</h2>
<?php if(empty($_SESSION['cart'])): ?>
<p>No items in cart. <a href="index.php">Shop Now</a></p>
<?php else: ?>
<table border=1 cellpadding=10 style="margin:auto; border-collapse:collapse;">
<tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
<?php
$total=0;
foreach($_SESSION['cart'] as $name=>$d){
    $sub=$d['price']*$d['quantity'];
    $total+=$sub;
    echo "<tr><td>$name</td><td>{$d['price']}</td><td>{$d['quantity']}</td><td>$sub</td></tr>";
}
$delivery=$total<=500?12:24;
$grand=$total+$delivery;
?>
<tr><th colspan=3>Total</th><th><?=$total?></th></tr>
<tr><th colspan=3>Delivery</th><th><?=$delivery?></th></tr>
<tr><th colspan=3>Grand Total</th><th><?=$grand?></th></tr>
</table>
<p><strong>Your order has been placed successfully!</strong></p>
<?php unset($_SESSION['cart']); ?>
<p><a href="index.php"><button>Continue Shopping</button></a></p>
<?php endif; ?>

<?php else: ?>
<p>Page not found</p>
<?php endif; ?>

</div>
</body>
</html>
