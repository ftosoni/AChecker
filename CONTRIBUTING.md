# Contributing to MediaWiki Accessibility Checker

Thank you for your interest in contributing! We welcome contributions from the community to help improve the accessibility of MediaWiki projects.

## 🛠️ Development Setup

To set up a development environment, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/ftosoni/mediawiki-accessibility-checker.git
   cd mediawiki-accessibility-checker
   ```

2. **Install dependencies**:
   Ensure you have [Composer](https://getcomposer.org/) installed.
   ```bash
   composer install
   ```

3. **Configure the application**:
   Create an empty configuration file to start the installation process:
   ```bash
   touch include/config.inc.php
   ```
   Or use the provided `include/config.inc.php` if you are migrating from another instance.

4. **Database Setup**:
   For local development, you can use MySQL or SQLite. If you want to use SQLite (recommended for Toolforge):
   ```bash
   php convert_to_sqlite.php
   ```

## 🧪 Testing and Quality

Before submitting a Pull Request, please ensure your changes do not break existing functionality.

### Manual Testing
Currently, we use manual testing for validation logic. You can run the following scripts to verify specific components:
- `test_validate.php`
- `test_parse.php`

## 📜 Technical Guidelines

Please review our technical considerations:
- **PHP Compatibility**: Support both PHP 7.4 and PHP 8.1+.
- **Toolforge Limits**: Stay within the **6 GiB RAM limit** for Toolforge webservices.
- **Persistence**: Use SQLite for metadata persistence on Toolforge.

## 🤝 Contribution Process

1. **Open an Issue**: For any major changes, please open an issue first to discuss what you would like to change.
2. **Create a Branch**: Use a descriptive branch name (e.g., `fix/label-check` or `feature/new-guideline`).
3. **Submit a Pull Request**: Provide a clear description of the changes and link to the relevant issue.