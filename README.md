# Laravel Slow Queries


## Installation

Install the package via Composer:

```bash
composer require ritey/laravel-slow-queries
```

## Usage

1. **Publish the configuration (optional):**
   
   ```bash
   php artisan vendor:publish --provider="Ritey\\SlowQueries\\SlowQueriesServiceProvider"
   ```

2. **Configure thresholds and email settings:**
   
   Edit `config/slow-queries.php` as needed.

3. **Send the slow queries report via email:**
   
   ```bash
   php artisan slow-queries:email-report
   ```


### Code Structure (for contributors)
- `src/Console/EmailSlowQueriesReport.php`: Command to email slow query reports.
- `src/Mail/SlowQueriesReport.php`: Mailable for the report.
- `src/Support/Paths.php`: Helper for file paths.
- `resources/views/emails/slow_queries_report_plain.blade.php`: Email template.
- `config/slow-queries.php`: Configuration file.

### Testing
- Add your tests and run them using PHPUnit:
  ```bash
  ./vendor/bin/phpunit
  ```

### Contributing
- Fork the repository and create a feature branch.
- Submit a pull request with a clear description of your changes.

### Coding Standards
- Follow PSR-12 coding style.
- Run `composer lint` if a linter is configured.

### License
See [LICENSE](LICENSE) for details.
