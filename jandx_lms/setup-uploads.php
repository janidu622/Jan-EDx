
<?php
// Create necessary upload directories
$directories = [
    'uploads',
    'uploads/materials',
    'uploads/assignments',
    'uploads/submissions'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Create .htaccess file for uploads security
$htaccess_content = 'Options -Indexes
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>';

file_put_contents('uploads/.htaccess', $htaccess_content);
echo "Created .htaccess file for upload security\n";

echo "\nSetup complete! Your LMS is ready for file uploads.\n";
?>