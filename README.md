# Development

Note that the plugin itself is located in the `power-captcha` directory and does not use Composer. 

Composer is only used for development to check and fix WordPress Coding Standards.

## Setup for Development

1. **Clone the project**  
   Clone the repository to your local machine.

2. **Install development dependencies**  
   Install the required dependencies via Composer:
   ```
   cd integration-wordpress
   composer install --dev
   ```

3. **Create a symbolic link in the `wp-content/plugins` directory**  
   Create a symbolic link to `integration-wordpress/power-captcha` in the WordPress plugins directory:
   ```
   PS> New-Item -ItemType SymbolicLink -Path "<WordPress>/wp-content/plugins/power-captcha" -Target "<Workspace>/integration-wordpress/power-captcha"
   ```
   *(Example using Windows PowerShell)*

## Development Utilities

### Test code against WordPress Coding Standards (WPCS)
To check whether your code complies with the WordPress Coding Standards, execute this command to get a report:
```
cd integration-wordpress
composer cs
```

### Fix code to comply with WordPress Coding Standards
To automatically format and fix your code to match the WordPress Coding Standards, use:
```
cd integration-wordpress
composer cbf
```