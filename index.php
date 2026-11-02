<?php
session_start();
$_SESSION['tasks'] = $_SESSION['tasks'] ?? [];

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? ['task_name' => '', 'task_description' => ''];
unset($_SESSION['errors'], $_SESSION['old']);

function findIndex(string $id): ?int {
    foreach ($_SESSION['tasks'] as $i => $t) {
        if ($t['id'] === $id) return $i;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = $_POST['id'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $name = trim($_POST['task_name'] ?? '');
        $desc = trim($_POST['task_description'] ?? '');

        if ($name === '') {
            $errors[] = 'Task name cannot be empty.';
        } elseif (strlen($name) < 3) {
            $errors[] = 'Task name must be at least 3 characters long.';
        } elseif (strlen($name) > 60) {
            $errors[] = 'Task name cannot exceed 60 characters.';
        }
        if ($desc === '') {
            $errors[] = 'Task description is required.';
        } elseif (strlen($desc) > 255) {
            $errors[] = 'Task description cannot exceed 255 characters.';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['task_name' => $name, 'task_description' => $desc];
            header('Location: index.php' . ($action === 'update' ? '?edit=' . urlencode($id) : ''));
            exit();
        }

        if ($action === 'add') {
            $_SESSION['tasks'][] = [
                'id' => uniqid('t_'), 'name' => $name, 'description' => $desc, 'done' => false,
            ];
        } elseif (($i = findIndex($id)) !== null) {
            $_SESSION['tasks'][$i]['name'] = $name;
            $_SESSION['tasks'][$i]['description'] = $desc;
        }
    } elseif ($action === 'toggle' && ($i = findIndex($id)) !== null) {
        $_SESSION['tasks'][$i]['done'] = !$_SESSION['tasks'][$i]['done'];
    } elseif ($action === 'delete' && ($i = findIndex($id)) !== null) {
        array_splice($_SESSION['tasks'], $i, 1);
    } elseif ($action === 'clear_done') {
        $_SESSION['tasks'] = array_values(array_filter($_SESSION['tasks'], fn($t) => !$t['done']));
    }

    header('Location: index.php');  
    exit();
}

$editTask = null;
if (isset($_GET['edit']) && ($i = findIndex($_GET['edit'])) !== null) {
    $editTask = $_SESSION['tasks'][$i];
    if ($old['task_name'] === '' && $old['task_description'] === '') {
        $old = ['task_name' => $editTask['name'], 'task_description' => $editTask['description']];
    }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$total = count($_SESSION['tasks']);
$done  = count(array_filter($_SESSION['tasks'], fn($t) => $t['done']));
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Plain Tasks</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="site-header">
    <h1>Plain Tasks</h1>
    <p class="tally"><?= $done ?> of <?= $total ?> done</p>
  </header>

  <main>
    <section aria-labelledby="form-title" class="panel">
      <h2 id="form-title"><?= $editTask ? 'Edit task' : 'Add a task' ?></h2>

      <?php if ($errors): ?>
        <ul class="errors" role="alert">
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="POST" action="index.php" id="task-form" novalidate>
        <input type="hidden" name="action" value="<?= $editTask ? 'update' : 'add' ?>">
        <?php if ($editTask): ?>
          <input type="hidden" name="id" value="<?= e($editTask['id']) ?>">
        <?php endif; ?>

        <label for="task_name">Task name</label>
        <input type="text" id="task_name" name="task_name" maxlength="60" required
               value="<?= e($old['task_name']) ?>">

        <label for="task_description">Description
          <span class="counter" id="desc-counter">0/255</span>
        </label>
        <textarea id="task_description" name="task_description" rows="3" maxlength="255" required><?= e($old['task_description']) ?></textarea>

        <div class="actions">
          <button type="submit" class="btn primary"><?= $editTask ? 'Save changes' : 'Add task' ?></button>
          <?php if ($editTask): ?>
            <a class="btn" href="index.php">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section aria-labelledby="list-title" class="panel">
      <div class="list-head">
        <h2 id="list-title">Your tasks</h2>
        <?php if ($done > 0): ?>
          <form method="POST" action="index.php" data-confirm="Remove all completed tasks?">
            <input type="hidden" name="action" value="clear_done">
            <button type="submit" class="link-btn">Clear completed</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($total === 0): ?>
        <p class="empty">Nothing here yet. Add your first task above.</p>
      <?php else: ?>
        <ul class="tasks">
          <?php foreach ($_SESSION['tasks'] as $task): ?>
            <li class="task<?= $task['done'] ? ' is-done' : '' ?>">
              <form method="POST" action="index.php" class="toggle">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                <button type="submit" class="check" aria-label="<?= $task['done'] ? 'Mark as not done' : 'Mark as done' ?>: <?= e($task['name']) ?>"
                        aria-pressed="<?= $task['done'] ? 'true' : 'false' ?>"></button>
              </form>

              <div class="body">
                <strong><?= e($task['name']) ?></strong>
                <p><?= e($task['description']) ?></p>
              </div>

              <div class="row-actions">
                <a class="link-btn" href="index.php?edit=<?= urlencode($task['id']) ?>">Edit</a>
                <form method="POST" action="index.php" data-confirm="Delete &quot;<?= e($task['name']) ?>&quot;?">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                  <button type="submit" class="link-btn danger">Delete</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>

  <script src="script.js"></script>
</body>
</html>
