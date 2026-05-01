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

## 🚀 Deployment (Toolforge - Recommended: SQLite)

Using SQLite is the easiest way to deploy AChecker on Toolforge as it requires zero server-side database configuration.

### 1. Create SQLite Database (Local)
Run the migration script on your local machine to convert your MySQL data into a portable file:
```bash
php convert_to_sqlite.php
```
This creates `database/achecker.db`.

### 2. Deploy to Toolforge
Log into Toolforge and clone the repository:

```bash
# Log into Toolforge
ssh your-username@login.toolforge.org

# Become the tool
become accessibility-checker

# Clone the repo
cd ~
git clone https://github.com/ftosoni/AChecker.git temp_repo
rm -rf public_html/*
cp -r temp_repo/* public_html/
rm -rf temp_repo
```

### 3. Upload the Database File
Upload the generated `.db` file to your tool's project directory:
```bash
# From your local machine
scp -r ./database your-username@login.toolforge.org:/data/project/accessibility-checker/
```

### 4. Configure Application
Edit `public_html/include/config.inc.php` on Toolforge:
1. Set `DB_TYPE` to `'sqlite'`.
2. Ensure `SQLITE_PATH` points to `/data/project/accessibility-checker/database/achecker.db`.

### 5. Start Webservice
```bash
toolforge webservice php7.4 start
```
Your tool will be available at `https://accessibility-checker.toolforge.org/`.


