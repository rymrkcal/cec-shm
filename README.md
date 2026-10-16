# CEC SHM

Sensor calculator for projects, blocks, and sensors. Each sensor uses either the ATG formula or the 3L formula.

Open the site at `http://localhost/CEC%20SHM/`. Run `setup.php` once to create the database and the first accounts.

## Folders

```
index.php              Login page
process_login.php      Checks the username and password
logout.php             Ends the session
setup.php              Creates the database tables once

config/database.php    MySQL connection only

css/style.css          All page styles
js/atg.js              ATG calculator interaction
js/3l.js               3L calculator interaction

admin/                 Admin pages and the forms that save or delete data
  home.php             Create or open a project
  project.php          One project: blocks, sensors, and the calculator
  atg.php              Sends an old sensor link to the project page
  create_*.php         Save a new project, block, or sensor
  delete_*.php         Delete a project, block, or sensor

client/home.php        Client page

php/                   Shared server code (not opened directly in the browser)
  auth.php             Stops a page unless an admin is logged in
  project_data.php     Loads one project and its blocks
  cleanup.php          Deletes a sensor's ATG or 3L rows
  icons.php            Save and delete icons
  calculations/atg/    ATG formula, saved data, and calculator screen
  calculations/3l/     3L formula, saved data, and calculator screen
```

Each formula folder has the same files. `parameter.php`, `measured.php`, `theoretical.php`, and `output.php` hold the formulas. `data.php` loads and saves. `page.php` is the calculator screen. `common.php` holds dates and numbers. The interaction scripts are `js/atg.js` and `js/3l.js`.
