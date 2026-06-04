<?php
if (mail("your-email@gmail.com", "Test Email", "This is a test message")) {
    echo "Mail sent successfully!";
} else {
    echo "Mail failed!";
}
?>