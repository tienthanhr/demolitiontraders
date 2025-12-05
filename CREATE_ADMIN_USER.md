# How to Create an Admin User

The default admin user has been removed from the database schema for security reasons. To create your first admin user, you need to run a command-line script from the root directory of the project.

## Prerequisites

- You must have PHP installed and accessible from your command line.
- You must have configured your database connection in the `.env` file.
- The database schema must be imported (`database/schema.sql`).

## Usage

1.  Open your terminal or command prompt.
2.  Navigate to the root directory of the `demolitiontraders` project.
3.  Run the following command, replacing the placeholders with your desired credentials:

    ```bash
    php backend/scripts/create_admin.php <email> <password> <first_name> <last_name>
    ```

### Example

To create an admin user with the email `admin@example.com`, password `a-very-strong-password`, and name "John Doe", you would run:

```bash
php backend/scripts/create_admin.php admin@example.com a-very-strong-password John Doe
```

## Important Notes

- **Choose a strong password.** Do not use a simple or common password.
- **Run this script only once** to create your initial administrator. You can create additional admins from the admin panel afterward.
- **Do not commit your `.env` file** with real database credentials to your repository.
