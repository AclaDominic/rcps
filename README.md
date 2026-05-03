 1. Clone the Project

Open Command Prompt and run:

git clone https://github.com/your-username/rcps.git
cd rcps\rcps_v1

 2. Install Dependencies
composer install
npm install
 3. Setup Environment

Create .env file:

copy .env.example .env

Then open .env and edit database:

DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=

4. Generate Key
php artisan key:generate
5. Run Database

Make sure XAMPP MySQL is running, then:

php artisan migrate
php artisan db:seed

 If may error, try:

php artisan migrate:fresh --seed

 6. Build Frontend
npm run build

 7. Run the Project

Open 2 CMD windows:

CMD 1 (Backend)
php artisan serve
CMD 2 (Frontend)
npm run dev

 Open in Browser

Go to:

http://localhost:8000

Admin panel:

http://localhost:8000/admin
Default Login

Email: admin@example.com
Password: password
 Shortcut (Easiest Way)

Instead of manual setup, you can run:

composer run install-project-win

Then:

php artisan serve