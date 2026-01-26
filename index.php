<?php include 'db.php';?>
<?php

if(isset($_GET['update'])){
    $id = (int)$_GET['update'];
    $result = $conn->query("SELECT * FROM tasks WHERE id=$id");
    $editTask = $result->fetch_assoc();

    if(!$editTask){
        header("$location");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP To-Do List</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        h2{
            color:#333;
        }
        form{
            margin-bottom:20px;
        }
        input[type="text"]{
            padding:8px;
            width:250px;
        }
        button{
            padding:8px 12px;
        }
        ul{
            list-style-type:none;
            padding:0;
        }
        li{
            margin: 8px;
            padding: 8px;
            background-color: #f4f4f4;
            border-radius: 5px;
        }
        .done{
            text-decoration: line-through;
            color: gray;
        }
        a{
            color:red;
            text-decoration:none;
            margin-left:10px;
        }
    </style>
</head>
<body>
    
    <h2>Zedric To-Do List</h2>

    <form method="POST" action="index.php">
        <input type="text" name="task" placeholder="Enter a new task" required>
        <button type="submit" name="add">Add Task</button>
    </form>
    <?php if ($editTask): ?>
        <form method="POST" action="index.php">
            <input type="hidden" name="id" value="<?= $editTask['id']; ?>">
            <input type="text" name="task" value="<?= htmlspecialchars($editTask['task'])?>" placeholder="Enter a new Task" required>
            <button type="submit" name="update">
                Update Task
            </button>
            <a href="index.php" style="margin-left:10px;">Cancel</a>
        </form>
    <?php endif; ?>

    <ul>
        <?php
        
        if(isset($_POST['add'])){
            $task = $conn->real_escape_string($_POST['task']);
            $conn->query("INSERT INTO tasks (task) VALUES ('$task')");
            header($location);
            exit();
        }

        if(isset($_GET['done'])){
            $id = (int)$_GET['done'];
            $conn->query("UPDATE tasks SET done=1 WHERE id=$id");
            header($location);
            exit();
        }

        if(isset($_GET['undo'])){
            $id = (int)$_GET['undo'];
            $conn->query("UPDATE tasks SET done=0 WHERE id=$id");
            header($location);
            exit();
        }

        if(isset($_POST['update'])){
            $id = (int)$_POST['id'];
            $task = $conn->real_escape_string($_POST['task']);
            $conn->query("UPDATE tasks SET task='$task' WHERE id=$id");
            header($location);
            exit();
        }

        if(isset($_GET['delete'])){
            $id = (int)$_GET['delete'];
            $conn->query("DELETE FROM tasks WHERE id=$id");
            header($location);
            exit();
        }

        $result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");
        while($row = $result->fetch_assoc()){
            $doneClass = $row['done'] ? 'done' : '';

            echo "<li class='$doneClass'>" . htmlspecialchars($row['task']);
            
            if(!$row['done']){
                echo "<a href='index.php?done=" . $row['id'] . "'>Done</a>" . 
                "<a href='index.php?update={$row['id']}'>Edit</a>";
            } else {
                echo "<a href='index.php?undo=" . $row['id'] . "'>Undo</a>" . 
                "<span style='color:gray; margin-left:10px;'>Edit</span>";
            }
             
            echo" <a href='index.php?delete=" . $row['id'] . "' onclick=\"return confirm('Are you sure?')\">Delete</a>" . 
                "</li>";
        }
        ?>
    </ul>
    
</body>
</html>