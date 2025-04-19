
### Database Setup

- Create the database and tables using the provided SQL script:

```sh
mysql -u your_db_user -p garbage_routes < db_setup.sql
```

- This will also create a test admin account:
  - **Username:** `admin`
  - **Password:** `admin`

### Run the App

- Deploy on your local web server (Apache, Nginx, etc.) and visit the site in your browser.

---

## Known Issues

- **Workspace Bugs**:  
  - Uploading routes does not always work as expected.
  - Saving workspaces to the cloud/database is currently unreliable.
- **UI/UX**:  
  - Some features may not be fully responsive or intuitive.
- **Error Handling**:  
  - Some errors are not user-friendly or lack feedback.
- **Testing**:  
  - Automated tests are not yet implemented.

*This project is a work in progress and not production-ready. Contributions and bug reports are welcome!*

---

## About

SanitationRoutePlanner is a personal side project by Agarhouse Development to help modernize route planning in the waste industry.  
Feel free to fork, contribute, or reach out with ideas!
