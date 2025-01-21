<?php
// logout.php
session_start();
setcookie('user_uuid', '', time() - 3600, "/");
session_unset();
session_destroy();
header('Location: index.php?message=' . urlencode('UUID został usunięty. Twoje torrenty nie będą już dostępne.') . '&type=' . urlencode('warning'));
exit;
?>
