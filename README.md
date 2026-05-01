# MediaWiki Accessibility Checker

AChecker is an automated accessibility checker used to evaluate the accessibility of HTML pages, and help ensure they can be accessed by all individuals, including those with disabilities, using assistive technologies to navigate the Internet.

AChecker live site: https://achecker.achecks.ca

What sets AChecker apart from other automated accessibility checkers?

- Reviewers can interact with the system to make decisions on potential barriers that automated checkers can not determine with certainty.
- Choose from a range of accessibility standards to review conformance with various international accessibility requirements.
- Design custom accessibility guidelines tailored specifically to your organization
- View existing guidelines in AChecker to see exactly what it is reviewing.
- Design new accessibility checks and have them added to AChecker.

## Requirements

- PHP 7+
- MySQL 4.1.13+
- Composer
- Ensure that the ```mysqli``` extension is enabled.

## ChangeLog

See [Changelog](CHANGELOG.md)


## Contribution
 If you are interested in contributing to this project, then follow the [instructions here](CONTRIBUTING.md).

## Installation

- Clone this repository
- Be sure to have Composer [setup on your system/server](https://getcomposer.org/doc/00-intro.md)
- Follow this [instructions](https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies) to install dependencies
- Open a web browser to access the installation directory where AChecker was installed or cloned to
- Follow the instructions provided by the installer.

Note, if you are installing from a Git clone of AChecker, before following the instructions above, you will need to create an empty configuration file. In the AChecker directory, at the command prompt, issue the following command:

```
touch include/config.inc.php
```

Then follow the instructions above.

For more about using AChecker, see the instructional videos on [YouTube](http://www.youtube.com/watch?v=jtNyF7KuOk8).

---

## 🚀 Deployment (Toolforge)

Follow these steps to deploy the MediaWiki Accessibility Checker on Wikimedia Toolforge. This application requires a PHP environment and a MariaDB database.

> [!NOTE]
> The examples below use `mediawiki-accessibility-checker` as the tool name. Replace this with your own Toolforge credentials where applicable.

### 1. Database Setup
The database is **CRITICAL** for AChecker. It stores not only user logins but also all accessibility guidelines, checks, and application configurations.

1. **Export your local database**:
   ```bash
   mysqldump -u root achecker > achecker_dump.sql
   ```

2. **Upload the dump to Toolforge**:
   ```bash
   scp achecker_dump.sql your-username@login.toolforge.org:~/
   ```

3. **Import the database on Toolforge**:
   Log into Toolforge, become your tool, and import:
   ```bash
   ssh your-username@login.toolforge.org
   become accessibility-checker
   sql achecker < ~/achecker_dump.sql
   ```

### 2. Upload Files
Upload the project files to the `public_html` directory of your tool:

```bash
# From your local project root
scp -r ./* your-username@login.toolforge.org:~/public_html/
```

### 3. Configuration
You must update `include/config.inc.php` on Toolforge to match the environment. 

1. **Database Credentials**: Use the credentials found in `~/replica.my.cnf`.
2. **Database Host**: Use `tools.db.svc.wikimedia.cloud`.
3. **Paths**: Ensure `AC_TEMP_DIR` points to a writable directory on Toolforge (e.g., `/data/project/accessibility-checker/temp/`).

### 4. Start Webservice
Start the PHP 7.4 webservice:

```bash
become accessibility-checker
toolforge webservice php7.4 start
```

### 5. Monitor Logs
If you encounter issues (like the PDF generator error), monitor the logs:
```bash
toolforge webservice logs -f
```


