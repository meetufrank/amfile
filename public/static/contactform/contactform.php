<?php

if (isset($_POST['submit'])) {
$name = $_POST['name'];
$number = $_POST['number'];
$email = $_POST['email'];
$question = $_POST['question'];

$mailTo = "sweet.appleseed@gmail.com";
$headers = "From: ".$mailFrom;
$txt = "You have received an e-mail from ".$name.".\n\n".$question;

mail($mailTO, $name, $txt, $headers);
header("Location: index.php?mailsend");
}
