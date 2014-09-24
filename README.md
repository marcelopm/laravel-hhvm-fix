laravel-hhvm-fix
================

A simple artisian task to replace the usage of compact function by array declarations across all files from a given folder (i.e vendor)

To add to artisan command list, just open artisan.php and include:

Artisan::add(new LaravelHHVMFixCommand());
