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

Follow these steps to deploy the application on Wikimedia Toolforge.

### 1. Initialize Database
Before uploading files, you must create your tool's private database to generate your credentials:
1.  Go to **[toolsadmin.wikimedia.org](https://toolsadmin.wikimedia.org/tools/id/accessibility-checker/databases)**.
2.  Log in and click **"Create Database"**.
3.  Wait 1-2 minutes for the system to generate your `~/.my.cnf` file.

### 2. Deploy Code via Git
Instead of using `scp`, it is recommended to clone the repository directly on the Toolforge bastion:

```bash
# Log into Toolforge
ssh your-username@login.toolforge.org

# Become the tool
become accessibility-checker

# Navigate to your home directory
cd ~

# Clone the repo into a temporary folder
git clone https://github.com/ftosoni/AChecker.git temp_repo

# Move files to public_html (ensure public_html is empty first)
rm -rf public_html/*
cp -r temp_repo/* public_html/
rm -rf temp_repo
```

### 3. Import Database Dump
Upload your local SQL dump and import it into the new Toolforge database:

```bash
# From your local machine
scp achecker_dump.sql your-username@login.toolforge.org:/data/project/accessibility-checker/

# On Toolforge (as the tool user)
# Find your DB name via: mysql --defaults-file=$HOME/.my.cnf -e "SHOW DATABASES;"
mysql --defaults-file=$HOME/.my.cnf sXXXXX__achecker < /data/project/accessibility-checker/achecker_dump.sql
```

### 4. Configure Application
Update `include/config.inc.php` on the server with your Toolforge database details:
- **DB Host**: `tools.db.svc.wikimedia.cloud`
- **DB User**: (Found in `~/.my.cnf`)
- **DB Pass**: (Found in `~/.my.cnf`)
- **DB Name**: `sXXXXX__achecker`

### 5. Start Webservice
Start the PHP 7.4 webservice:

```bash
toolforge webservice php7.4 start
```

Your tool will be available at `https://accessibility-checker.toolforge.org/`.


