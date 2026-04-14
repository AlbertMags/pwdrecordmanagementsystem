RewriteEngine On
# Updated RewriteBase to match your new folder location at localhost/PWD/
RewriteBase /PWD/

# 1. Force remove trailing slashes from URLs
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ $1 [L,R=301]

# 2. Specific Rule for Search Results
# Maps /search/name to index.php?page=search_results&q=name
RewriteRule ^search/([^/]+)$ index.php?page=search_results&q=$1 [L,QSA]

# 3. Specific Rule for Viewing PWD Profile
# Maps /view_search_pwd/123 to index.php?page=view_search_pwd&id=123
RewriteRule ^view_search_pwd/([0-9]+)$ index.php?page=view_search_pwd&id=$1 [L,QSA]

# 4. Specific Rule for Printing ID
# Maps /print_id/123 to print_id.php?id=123
RewriteRule ^print_id/([0-9]+)$ print_id.php?id=$1 [L,QSA]

# 5. Do not rewrite if the file or folder actually exists (Images, CSS, JS)
# This prevents the server from trying to route actual files through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# 6. General Page Routing (Send everything else to index.php)
# Maps /information_hub to index.php?page=information_hub
RewriteRule ^([^/]+)$ index.php?page=$1 [L,QSA]