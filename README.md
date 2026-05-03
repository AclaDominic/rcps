# RCPS — Resource-Constrained Project Scheduling

A Laravel + Filament web application for managing and scheduling projects under resource constraints, with AI-assisted task generation and intelligent workload computation.

---

## 📋 Requirements

| Tool | Version |
|------|---------|
| PHP | ^8.0.2 |
| Composer | Latest |
| Node.js | ^16+ |
| npm | Latest |
| MySQL | 5.7 / 8.0+ |
| XAMPP / any MySQL server | — |

---

## 🚀 How to Run the System

### Step 1 — Clone the Project

```cmd
git clone https://github.com/your-username/rcps.git
cd rcps\rcps_v1
```

---

### Step 2 — Install Dependencies

```cmd
composer install
npm install
```

---

### Step 3 — Setup Environment

Create `.env` file:

```cmd
copy .env.example .env
```

Then open `.env` and edit the database section:

```env
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```

---

### Step 4 — Generate Application Key

```cmd
php artisan key:generate
```

---

### Step 5 — Run Database Migrations & Seeders

> ⚠️ Make sure XAMPP MySQL (or your MySQL server) is running first.

```cmd
php artisan migrate
php artisan db:seed
```

If you encounter errors, reset with:

```cmd
php artisan migrate:fresh --seed
```

---

### Step 6 — Build Frontend Assets

```cmd
npm run build
```

---

### Step 7 — Start the Application

Open **2 separate CMD windows** inside `rcps_v1\`:

**CMD 1 — Backend server:**
```cmd
php artisan serve
```

**CMD 2 — Frontend (hot reload):**
```cmd
npm run dev
```

---

### Open in Browser

- **App:** http://localhost:8000
- **Admin Panel:** http://localhost:8000/admin

**Default Login:**
```
Email:    admin@example.com
Password: password
```

---

### ⚡ Shortcut (Easiest Way — Windows)

Run everything in one command:

```cmd
composer run install-project-win
```

Then start the server:

```cmd
php artisan serve
```

---

---

## 🧮 Overall Computation — `projects/create`

When creating a project, the system automatically runs a **multi-factor computation** across all **main tasks and subtasks** before the project is saved. This gives you a real-time preview of the full project workload.

---

### How It Works

When you fill in the project form and click **"Compute All"** or trigger the AI generation, the system performs the following calculations across every main task and all of its subtasks:

---

#### 1. 📊 Complexity Score (per task)

Each task is scored on a **weighted 5-factor model** (total = 100%):

| Factor | Weight | What it measures |
|--------|--------|-----------------|
| **Keyword Complexity** | 35% | Presence of high-tech keywords (API, OAuth, Docker, Redis, etc.) |
| **Subtask Count** | 25% | Number of subtasks — more subtasks = more decomposition needed |
| **Timeline Pressure** | 20% | Days-per-task ratio between start and end date |
| **Description Completeness** | 15% | Total word count across main + subtask descriptions |
| **Task Name Signals** | 5% | Technical signals in the task name itself |

**Complexity Levels:**

| Score | Level |
|-------|-------|
| 80–100 | 🔴 Very High |
| 65–79 | 🟠 High |
| 50–64 | 🟡 Medium |
| 35–49 | 🟢 Low |
| 0–34 | ⚪ Very Low |

---

#### 2. 🎯 Priority Assignment (per task)

After scoring complexity, each task is assigned a priority using a **score-based distribution**:

| Priority | Description |
|----------|-------------|
| **High (1)** | Critical path tasks, many dependencies, high risk |
| **Normal (2)** | Standard workload tasks |
| **Low (3)** | Low-risk, minimal dependencies |

Priority distribution adapts to total task count:
- **≤ 5 tasks:** 60% High / 30% Normal / 10% Low
- **6–15 tasks:** 30% High / 50% Normal / 20% Low
- **16+ tasks:** 20% High / 60% Normal / 20% Low

---

#### 3. 👥 Workload-Balanced Assignment (per task)

Each task is automatically assigned to a team member using a **suitability score** (max 100 pts):

| Factor | Weight |
|--------|--------|
| Current workload vs capacity | 40% |
| Experience (past ticket count) | — bonus/penalty |
| Priority-capacity matching | 20% |
| Skill requirements match | 20% |
| Estimated task hours | 10% |

> The system enforces an **8-hour daily session cap** per user. Tasks exceeding capacity are redistributed to the next best available team member.

---

#### 4. 🔁 Global / Overall Computation (All Tasks Combined)

The **"Total Computation"** view aggregates metrics across all main tasks and subtasks to give a unified project overview:

| Metric | Description |
|--------|-------------|
| **Total Estimated Hours** | Sum of all tasks' estimated hours |
| **Total Workload per User** | Hours distributed across each team member |
| **Overall Complexity** | Weighted average complexity score for the project |
| **Priority Distribution** | Count of High / Normal / Low tasks across the entire project |
| **Critical Path Tasks** | Tasks flagged as blockers for the whole timeline |
| **Team Capacity Remaining** | Remaining hours per user after all assignments |

---

### Preview in the Create Form

When you are on `projects/create`:

1. Fill in the **Project Details** (name, start/end date, description).
2. Add **Main Tasks** with their subtasks and subtask count.
3. Click **"Generate AI Tasks"** — the system instantly computes:
   - Complexity score for each main task + subtask
   - Priority assignments
   - Responsible user assignments (workload-balanced)
4. Click **"Compute All / Total Computation"** — a **global summary panel** appears showing:
   - Aggregated hours, role-based distribution, and per-user totals
   - Overall project complexity level
   - Combined priority breakdown across all tasks

> 💡 **Nothing is saved yet at this stage.** The computation is a live preview so you can review and adjust before confirming project creation.

---

## 🛠️ Common Commands

| Command | Description |
|---------|-------------|
| `php artisan serve` | Start the Laravel dev server |
| `npm run dev` | Start Vite with hot reload |
| `npm run build` | Build frontend for production |
| `php artisan migrate` | Run migrations |
| `php artisan migrate:fresh --seed` | Reset and re-seed DB |
| `php artisan db:seed` | Run seeders only |
| `php artisan queue:work` | Process background jobs |
| `php artisan optimize:clear` | Clear all cached files |
| `composer dump-autoload` | Refresh autoload |

---

## 📄 License

Licensed under the [MIT License](rcps_v1/LICENSE.md).