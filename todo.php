<?php

$servername = "localhost";
$username = "root";
$password = "toor";
$dbname = "todo";
$current_date = date("Y-m-d H:i:s");
$author_id = 'royb2300'; //will be currently logged in User
$member_id = 'admin'; //will be currently logged in User
$user_id = 'royb2300'; //currently logged in user
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Show records by search date
if(isset($_REQUEST['search_date']) && $_REQUEST['search_date']!=''){
  $search_date = $_REQUEST['search_date'];
  
  // $sql_for_open = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.member_id = m.username where t.status = 'new'
  // and t.deadline >= CONCAT('$search_date', ' 00:00:00') and t.deadline < CONCAT('$search_date', ' 23:59:59') order by t.deadline ASC";  
  
  $sql_for_open = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.member_id = m.username where t.status = 'new'
  and t.deadline >= CONCAT('$search_date', ' 00:00:00') and t.deadline < CONCAT('$search_date', ' 23:59:59') ORDER BY
     (CASE 
        WHEN (t.created_at != t.deadline and t.deadline < CONCAT(CURDATE(), ' 00:00:00'))  THEN t.deadline
      END) ASC,
     (CASE 
        WHEN (t.created_at = t.deadline and t.deadline < CONCAT(CURDATE(), ' 00:00:00'))  THEN t.deadline
      END) DESC,
     (CASE 
        WHEN (t.created_at != t.deadline and t.deadline < CONCAT(CURDATE(), ' 23:59:59'))  THEN t.deadline
      END) ASC";
  $sql_for_completed = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.member_id = m.username where t.status = 'completed' 
  and t.completed_at >= CONCAT('$search_date', ' 00:00:00') and t.completed_at < CONCAT('$search_date', ' 23:59:59') order by t.completed_at DESC";
  $searchResultNew = $conn->query($sql_for_open);
  $searchResultCompleted = $conn->query($sql_for_completed);
}

// Action perform after submit notes for a job
if(isset($_REQUEST['addNoteSubmit']) && isset($_REQUEST['addNoteSubmit']) == 'submit'){
  $todo_list_id = $_REQUEST['todo_id'];
  $note = $_REQUEST['note'];
  $sql = "INSERT INTO todo_list_notes (todo_list_id, note, author_id)
    VALUES ('$todo_list_id', '$note',  '$author_id')";
  if ($conn->query($sql) === TRUE) {
      $self = $_SERVER['SCRIPT_NAME'];  
      header("Location: $self");
  } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

// Change the status of the job either deleted or completed
if(isset($_REQUEST['status'])){
  $status = $_REQUEST['status'];
  $id = $_REQUEST['id'];
  if($status == 'completed'){
    $sql = "UPDATE todo_list set status = '$status', completed_by = '$user_id', completed_at = '$current_date' where id='$id'";
  }else{
    $sql = "UPDATE todo_list set status = '$status', deleted_by = '$user_id', deleted_at = '$current_date' where id='$id'";
  }
  if ($conn->query($sql) === TRUE) {
    $self = $_SERVER['SCRIPT_NAME'];  
    header("Location: $self");
  }else{
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

// Function to get notes for a todo list with ID.
function getNotes($todo_id){
  $servername = "localhost";
  $username = "root";
  $password = "toor";
  $dbname = "todo";

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  $sql = "SELECT tln.note, m.username as author_name, tln.created_at
  from todo_list_notes tln
  left join user_master m
  on tln.author_id = m.username where tln.todo_list_id = $todo_id";

  $notesRes = $conn->query($sql);
  $return = array();
  if ($notesRes->num_rows > 0) {
    while($row = $notesRes->fetch_assoc()) {
      array_push($return, $row);
    }
  }
  return $return;
}

// Action when adding a new todo for today or specific date(future only)
if(isset($_REQUEST['submit']) && isset($_REQUEST['submit']) == 'add'){
  $title = $_REQUEST['title'];
  $deadline = empty($_REQUEST['deadline']) ? $current_date : date("Y-m-d H:i:s", strtotime($_REQUEST['deadline']));
  $content = $_REQUEST['content'];
  $sql = "INSERT INTO todo_list (title, content, created_at, deadline, member_id)
  VALUES ('$title', '$content','$current_date', '$deadline', '$member_id')";
  if ($conn->query($sql) === TRUE) {
      $self = $_SERVER['SCRIPT_NAME'];  
      header("Location: $self");
  } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

// Get all jobs that are open and completed
// $sql_for_open = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.member_id = m.username where t.status = 'new'
// and t.created_at < CONCAT(CURDATE(), ' 23:59:59') order by t.created_at ASC";

$sql_for_open = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.member_id = m.username where t.status = 'new'
and (t.created_at < CONCAT(CURDATE(), ' 23:59:59') and t.created_at = t.deadline or t.deadline < CONCAT(CURDATE(), ' 23:59:59')) ORDER BY
     (CASE 
        WHEN (t.created_at != t.deadline and t.deadline < CONCAT(CURDATE(), ' 00:00:00'))  THEN t.deadline
      END) ASC,
     (CASE 
        WHEN (t.created_at = t.deadline and t.deadline < CONCAT(CURDATE(), ' 00:00:00'))  THEN t.deadline
      END) DESC,
     (CASE 
        WHEN (t.created_at != t.deadline and t.deadline < CONCAT(CURDATE(), ' 23:59:59'))  THEN t.deadline
      END) ASC";
$sql_for_completed = "SELECT t.*, m.username FROM todo_list t left join user_master m on t.completed_by = m.username where t.status = 'completed'
and t.completed_at >= CONCAT(CURDATE(), ' 00:00:00') and t.completed_at < CONCAT(CURDATE(), ' 23:59:59') order by t.completed_at DESC";

$new_result = $conn->query($sql_for_open);
$completed_result = $conn->query($sql_for_completed);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.css" rel="stylesheet"/>
  <!--   <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"> -->
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker.min.css" />
  -->
  <link href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/css/bootstrap-datetimepicker.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.0/jquery-confirm.min.css">
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.js"></script>
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/js/bootstrap-datepicker.min.js"></script> -->
  <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.12.0/moment.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.0/jquery-confirm.min.js"></script>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="container">
  <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading" id="addToDoPanel" style="cursor: pointer;">
            <h2>Add To Do List<span class="pull-right" ><i class="glyphicon glyphicon-plus"></i></span></h2>
          </div>
          <div class="panel-body" id="addToDoPanelBody">
            <form id="addToDo" method="POST">
              <div class="form-group">
              <label for="title">Title:</label>
              <input type="text" class="form-control" id="myInput" name="title" placeholder="Title...">
              </div>
              <div class="form-group">
              <label for="notes">Description:</label>
              <textarea class="form-control" rows="5" id="content" name="content" placeholder="Description..."></textarea>
              </div>
              <div class="form-group">
              <label for="pwd">Deadline:</label>
              <div class='input-group date' id='datetimepicker1'>
              <input type='text' name="deadline" class="form-control" />
              <span class="input-group-addon">
              <span class="glyphicon glyphicon-calendar"></span>
              </span>
              </div>
              </div>
              <button type="submit"  value="submit" name="submit" class="addBtn"> Add </button>
              <button type="button" class="pull-right btn btn-default" id="closeAddPanel"> Close </button>
              <!--     <button type="submit" class="btn btn-default">Submit</button>
              -->  
            </form>
          </div>
        </div>
      </div>

        <div class="col-md-12">
          <form class="form-inline" id="searchToDo" style="margin-bottom: 20px;">
            <label class="sr-only" for="inlineFormInputName2">Date</label>
            <div class='input-group date' id='datetimepicker2'>
            <input type='text' name="search_date" class="form-control" />
            <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
            </span>
            </div>
            <button type="submit" class="btn btn-primary" value="search">Search</button>
          </form>
        </div>

<!-- Section for showing today todo list  -->    
<?php if(!isset($_REQUEST['search_date'])){ ?>

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading" id="showToDo">
        <h2>To Do List(Today)</h2>
      </div>
      <div class="panel-body">  
        <form id="change_status" method="post">
          <input type="hidden" name="id" value="0">
          <input type="hidden" name="status" value="completed">
        </form>

        <ul id="myUL">
        <?php 
        if ($new_result->num_rows > 0) {
        // output data of each row
        while($row = $new_result->fetch_assoc()) { ?>
        <li class="short_details" id="<?= $row['id'] ?>"><?= $row['title'] ?>  
        

        <span class="pull-right">
        <?php 
        if($row['deadline']!= null && $row['deadline'] != $row['created_at']){ ?>
          <span class="label label-danger" style="margin-right: 3px;"> Deadline :- <?= $row['deadline']?> </span>
        <?php }
        ?>
        <!--  <button type="button" class="btn btn-info" data-toggle="modal" data-target="#notesModal" id="showNoteModal">Open Modal</button> -->
        <button type="button" class="btn btn-default" data-todo_id="<?= $row['id'] ?>" id="showNoteModal">+ Add Note</button>
          
        <?php if($row['status'] == 'new'){ ?>
        <button type="button" data-status="completed" class="btn btn-primary">Complete</button>

        <?php }elseif($row['status'] == 'completed'){ ?>
        <button type="button" class="btn btn-success disabled">Completed</button>  
        <?php       }?>
        <button type="button" data-status="deleted" class="btn btn-danger">Remove</button>
        </span>
        </li>
        <div class="full_details text-center" id="<?= $row['id'] ?>"> 
        <p class="task_description"> Description - <span class="<?php echo !empty($row['content']) ? '' : 'no_desc'; ?>">

        <?php echo !empty($row['content']) ? $row['content'] : 'No description'; ?></span> 
        <?php 
        if($row['status'] == 'completed'){ ?>
          <span class="label label-success pull-right"> Completed By <?= $row['username'] ?> On <?= $row['completed_at'] ?></span>
        <?php }else{ ?>
          <span class="label label-info pull-right"> Created By <?= $row['username'] ?> On <?= $row['created_at'] ?></span>  
        <?php }
        ?>
        </p>
        <?php $notes = getNotes($row['id']);
        // print_r($notes);
        if(count($notes) > 0){ ?>
        <ul class="taskNotes">
          <?php foreach ($notes as $key => $value) { ?>
            <li><?= $value['note'] ?> <span class="label label-primary pull-right">By <?= $value['author_name'] ?> On <?= $value['created_at'] ?></span> </li>
          <?php }
          ?>
          </ul>
        <?php }
         ?>
         </div>
        <?php //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        }
        } else { ?>
        <p>No todo list</p>
        <?php }

        ?>
        </ul>

      </div>
    </div>
  </div>

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading" id="showToDo">
      <h2>To Do List(Today) - Completed</h2>
      </div>
      <div class="panel-body">  
        <form id="change_status" method="post">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="status" value="completed">
        </form>

        <ul id="myUL">
        <?php
        if ($completed_result->num_rows > 0) {
        // output data of each row
        while($row = $completed_result->fetch_assoc()) {
         ?>
        <li class="short_details" id="<?= $row['id'] ?>"><?= $row['title'] ?>  
        <span class="pull-right">
         <button type="button" class="btn btn-default" data-todo_id="<?= $row['id'] ?>" id="showNoteModal">+ Add Note</button>

        <?php if($row['status'] == 'new'){ ?>
        <button type="button" data-status="completed" class="btn btn-primary">Complete</button>

        <?php }elseif($row['status'] == 'completed'){ ?>
        <button type="button" class="btn btn-success disabled">Completed</button>  
        <?php       }?>
        <button type="button" data-status="deleted" class="btn btn-danger">Remove</button>
        </span>
        </li>
        <div class="full_details text-center" id="<?= $row['id'] ?>"> 
        <p class="task_description"> Description - <span class="<?php echo !empty($row['content']) ? '' : 'no_desc'; ?>">

        <?php echo !empty($row['content']) ? $row['content'] : 'No description'; ?></span> 
        <?php 
        if($row['status'] == 'completed'){ ?>
          <span class="label label-success pull-right"> Completed By <?= $row['username'] ?> On <?= $row['completed_at'] ?></span>
        <?php }else{ ?>
          <span class="label label-info pull-right"> Created By <?= $row['username'] ?> On <?= $row['created_at'] ?></span>  
        <?php }
        ?>
        </p>
        <?php $notes = getNotes($row['id']);
        // print_r($notes);
        if(count($notes) > 0){ ?>
        <ul class="taskNotes">
          <?php foreach ($notes as $key => $value) { ?>
            <li><?= $value['note'] ?> <span class="label label-primary pull-right">By <?= $value['author_name'] ?> On <?= $value['created_at'] ?></span> </li>
          <?php }
          ?>
          </ul>
        <?php }
         ?>
         </div>
        <?php //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        }
        } else { ?>
        <p>No todo list</p>
        <?php }

        ?>
        </ul>
      </div>
    </div>
  </div> 
    <?php 
  }
if(isset($_REQUEST['search_date'])){ ?>
<!-- Section for showing searched date's todo list  -->    
      
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading" id="showToDo">
      <h2>To Do List For <?php echo $_REQUEST['search_date'] ?></h2>
      </div>
      <div class="panel-body">  
        <form id="change_status" method="post">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="status" value="completed">
        </form>
        <ul id="myUL">
        <?php 
        if ($searchResultNew->num_rows > 0) {
        // output data of each row
        while($row = $searchResultNew->fetch_assoc()) { ?>
        <li class="short_details" id="<?= $row['id'] ?>"><?= $row['title'] ?> 
        
        <span class="pull-right">
        <?php 
        if($row['deadline']!= null && $row['deadline'] != $row['created_at']){ ?>
        <span class="label label-danger" style="margin-right: 3px;"> Deadline :- <?= $row['deadline']?> </span>
        <?php }
        ?>
        <button type="button" class="btn btn-default" data-todo_id="<?= $row['id'] ?>" id="showNoteModal">+ Add Note</button>

        <?php if($row['status'] == 'new'){ ?>
        <button type="button" data-status="completed" class="btn btn-primary">Complete</button>

        <?php }elseif($row['status'] == 'completed'){ ?>
        <button type="button" class="btn btn-success disabled">Completed</button>  
        <?php       }?>
        <button type="button" data-status="deleted" class="btn btn-danger">Remove</button>
        </span>
        </li>
        <div class="full_details text-center" id="<?= $row['id'] ?>"> 
        <p class="task_description"> Description - <span class="<?php echo !empty($row['content']) ? '' : 'no_desc'; ?>">

        <?php echo !empty($row['content']) ? $row['content'] : 'No description'; ?></span> 
        <?php 
        if($row['status'] == 'completed'){ ?>
          <span class="label label-success pull-right"> Completed By <?= $row['username'] ?> On <?= $row['completed_at'] ?></span>
        <?php }else{ ?>
          <span class="label label-info pull-right"> Created By <?= $row['username'] ?> On <?= $row['created_at'] ?></span>  
        <?php }
        ?>
        </p>
        <?php $notes = getNotes($row['id']);
        // print_r($notes);
        if(count($notes) > 0){ ?>
        <ul class="taskNotes">
          <?php foreach ($notes as $key => $value) { ?>
            <li><?= $value['note'] ?> <span class="label label-primary pull-right">By <?= $value['author_name'] ?> On <?= $value['created_at'] ?></span> </li>
          <?php }
          ?>
          </ul>
        <?php }
         ?>
         </div>
        <?php //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        }
        } else { ?>
        <p>No todo list</p>
        <?php }

        ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading" id="showToDo">
      <h2>To Do List For <?php echo $_REQUEST['search_date'] ?> - Completed</h2>
      </div>
      <div class="panel-body">  
        <form id="change_status" method="post">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="status" value="completed">
        </form>
        <ul id="myUL">
        <?php 
        if ($searchResultCompleted->num_rows > 0) {
        // output data of each row
        while($row = $searchResultCompleted->fetch_assoc()) { ?>
        <li class="short_details" id="<?= $row['id'] ?>"><?= $row['title'] ?> 
        <span class="pull-right"> 
        <button type="button" class="btn btn-default" data-todo_id="<?= $row['id'] ?>" id="showNoteModal">+ Add Note</button>

        <?php if($row['status'] == 'new'){ ?>
        <button type="button" data-status="completed" class="btn btn-primary">Complete</button>

        <?php }elseif($row['status'] == 'completed'){ ?>
        <button type="button" class="btn btn-success disabled">Completed</button>  
        <?php       }?>
        <button type="button" data-status="deleted" class="btn btn-danger">Remove</button>
        </span>
        </li>
        <div class="full_details text-center" id="<?= $row['id'] ?>"> 
        <p class="task_description"> Description - <span class="<?php echo !empty($row['content']) ? '' : 'no_desc'; ?>">

        <?php echo !empty($row['content']) ? $row['content'] : 'No description'; ?></span> 
        <?php 
        if($row['status'] == 'completed'){ ?>
          <span class="label label-success pull-right"> Completed By <?= $row['username'] ?> On <?= $row['completed_at'] ?></span>
        <?php }else{ ?>
          <span class="label label-info pull-right"> Created By <?= $row['username'] ?> On <?= $row['created_at'] ?></span>  
        <?php }
        ?>
        </p>
        <?php $notes = getNotes($row['id']);
        // print_r($notes);
        if(count($notes) > 0){ ?>
        <ul class="taskNotes">
          <?php foreach ($notes as $key => $value) { ?>
            <li><?= $value['note'] ?> <span class="label label-primary pull-right">By <?= $value['author_name'] ?> On <?= $value['created_at'] ?></span> </li>
          <?php }
          ?>
          </ul>
        <?php }
         ?>
         </div>
        <?php //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        }
        } else { ?>
        <p>No todo list</p>
        <?php }

        ?>
        </ul>
      </div>
    </div>
  </div>
<?php } ?>
  </div>

<div class="modal fade" id="notesModal" role="dialog">
  <div class="modal-dialog">  
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add notes for Title</h4>
      </div>
         <form id="addNotes" method="POST">
         <input type="hidden" name="todo_id" value="">
      <div class="modal-body">       
        <div class="form-group">
        <label for="notes">Notes:</label>
        <textarea class="form-control" rows="5" id="note" name="note" placeholder="Notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-default" name="addNoteSubmit" value="submit">Add</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
      </form>
    </div>    
  </div>
</div>

</div>
</body>
</html>
<script type="text/javascript">
$(function() {
  var tomorrow = new Date();
  var minDate = new Date(tomorrow.setDate(tomorrow.getDate()));
  var minDate1 = new Date(tomorrow.setDate(tomorrow.getDate()+1));
  $('#addToDoPanelBody').hide();
  $('#addToDoPanel').click(function(){
    $(this).next('.panel-body').slideToggle();
    $(this).find('span > i').toggleClass('glyphicon-minus');
  });
  $('#datetimepicker1').datetimepicker({
      format: 'YYYY-MM-DD hh:mm a',
      minDate : moment()
  });
  $('#datetimepicker1, #datetimepicker2').datetimepicker({
      format: 'YYYY-MM-DD',
      minDate : minDate1
  });

  $("#addToDo").on('submit', function(e) {
    var value = $('#myInput').val();
    if(value == ''){
      alert("Please add title for ToDo List");
      e.preventDefault();
    }
  });
  $("#addNotes").on('submit', function(e) {
    var value = $('#note').val();
    if(value == ''){
      alert("Please add notes");
      e.preventDefault();
      $('#notesModal').modal('show');
    }
  });
  $("div.full_details").hide();
  $("ul#myUL li").click(function(event) {
      event.stopPropagation();
      var $target = $(event.target);
      $target.parent().find('div').not('#'+$target.attr('id')).each(function(i,v){
        if($(this).is(':visible')){
          $(this).slideToggle();
        }
      });
      $target.next("div").slideToggle();
  });
  $("button#closeAddPanel").click(function(){
    $('#addToDoPanelBody').slideToggle();
    $('#addToDoPanel').find('span > i').toggleClass('glyphicon-minus');
  });
  $("button#showNoteModal").click(function(){
    var todo_id = $(this).attr('data-todo_id');
    $('#notesModal').modal('show');
    $('form#addNotes > input[name="todo_id"]').val(todo_id);
  });
  $("button.btn-primary, button.btn-danger").click(function(){
    todo_id = $(this).parent().parent().attr("id");
    status = $(this).attr("data-status");

    if(status == 'deleted'){
      $.confirm({
        title: 'Confirm!',
        content: 'Are you sure want to remove this to do task!',
        buttons: {
        confirm: function () {
        $('form#change_status > input[name="id"]').val(todo_id);
        $('form#change_status > input[name="status"]').val(status);
        $('form#change_status').submit();
        },
        cancel: function () {
        return true;
        }
        }
      });
    }else{
    $('form#change_status > input[name="id"]').val(todo_id);
    $('form#change_status > input[name="status"]').val(status);
    $('form#change_status').submit();
    }

  });
});
</script>
