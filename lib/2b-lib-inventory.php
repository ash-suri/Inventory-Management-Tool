<?php
class Inventory {
  /* [DATABASE HELPER FUNCTIONS] */
  private $pdo = null;
  private $stmt = null;
  public $error = "";
  public $lastID = null;

  function __construct() {
  // __construct() : connect to the database
  // PARAM : DB_HOST, DB_CHARSET, DB_NAME, DB_USER, DB_PASSWORD

    // ATTEMPT CONNECT
    try {
      $str = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
      if (defined('DB_NAME')) { $str .= ";dbname=" . DB_NAME; }
      $this->pdo = new PDO(
        $str, DB_USER, DB_PASSWORD, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
      return true;
    }

    // ERROR - DO SOMETHING HERE
    catch (Exception $ex) {
      print_r($ex);
      die();
    }
  }

  function __destruct() {
  // __destruct() : close connection when done

    if ($this->stmt !== null) { $this->stmt = null; }
    if ($this->pdo !== null) { $this->pdo = null; }
  }

  function start() {
  // start() : auto-commit off

    $this->pdo->beginTransaction();
  }

  function end($commit=1) {
  // end() : commit or roll back

    if ($commit) { $this->pdo->commit(); }
    else { $this->pdo->rollBack(); }
  }

  function exec($sql, $data=null) {
  // exec() : run insert, replace, update, delete query
  // PARAM $sql : SQL query
  //       $data : array of data
 
    try {
      $this->stmt = $this->pdo->prepare($sql);
      $this->stmt->execute($data);
      $this->lastID = $this->pdo->lastInsertId();
    } catch (Exception $ex) {
      $this->error = $ex;
      return false;
    }
    $this->stmt = null;
    return true;
  }

  function fetch($sql, $cond=null, $key=null, $value=null) {
  // fetch() : perform select query
  // PARAM $sql : SQL query
  //       $cond : array of conditions
  //       $key : sort in this $key=>data order, optional
  //       $value : $key must be provided, sort in $key=>$value order

    $result = false;
    try {
      $this->stmt = $this->pdo->prepare($sql);
      $this->stmt->execute($cond);
      if (isset($key)) {
        $result = array();
        if (isset($value)) {
          while ($row = $this->stmt->fetch(PDO::FETCH_NAMED)) {
            $result[$row[$key]] = $row[$value];
          }
        } else {
          while ($row = $this->stmt->fetch(PDO::FETCH_NAMED)) {
            $result[$row[$key]] = $row;
          }
        }
      } else {
        $result = $this->stmt->fetchAll();
      }
    } catch (Exception $ex) {
      $this->error = $ex;
      return false;
    }
    $this->stmt = null;
    return $result;
  }
  
  /* [INVENTORY FUNCTIONS] */
  function getAll() {
  // getAll() : get all inventory & current stock

    $sql = "SELECT * FROM `inventory_stock`";
    $stock = $this->fetch($sql);
    return count($stock)==0 ? false : $stock ;
  }

  function get($part) {
  // get() : get part
  // PARAM $part : part number

    $sql = "SELECT * FROM `inventory_stock` WHERE `part_no`=?";
    $stock = $this->fetch($sql, [$part]);
    return count($stock)==0 ? false : $stock[0] ;
  }

  function getMvt($part) {
  // getMvt() : get stock movement of part
  // PARAM $part : part number

    $sql = "SELECT * FROM `inventory_movement` WHERE `part_no`=? ORDER BY `mvt_date` DESC";
    $stock = $this->fetch($sql, [$part]);
    return count($stock)==0 ? false : $stock ;
  }

  function add($part, $name, $stock=0, $desc="") {
  // add() : add new part
  // PARAM $part : part number
  //       $name : part name
  //       $stock : initial stock level
  //       $desc : part description (optional)

    // MAIN ENTRY
    $this->start();
    $sql = "INSERT INTO `inventory_stock` (`part_no`, `part_name`, `part_desc`, `part_stock`) VALUES (?, ?, ?, ?)";
    $cond = [$part, $name, $desc, $stock];
    $pass = $this->exec($sql, $cond);

    // INITIAL STOCK MOVEMENT
    if ($pass) {
      $sql = "INSERT INTO `inventory_movement` (`part_no`, `mvt_type`, `mvt_qty`) VALUES (?, 'i', ?);";
      $cond = [$part, $stock];
      $pass = $this->exec($sql, $cond);
    }

    // FINALIZE
    $this->end($pass);
    return $pass;
  }

  function edit($part, $name, $desc="", $newPart) {
  // edit() : edit part
  // PARAM $part : part number
  //       $name : part name
  //       $desc : part description (optional)
  //       $newPart : the new part number

    // MAIN ENTRY
    $this->start();
    if ($part!=$newPart) {
      $sql = "UPDATE `inventory_stock` SET `part_no`=?, `part_name`=?, `part_desc`=? WHERE `part_no`=?;";
      $cond = [$newPart, $name, $desc, $part];
    } else {
      $sql = "UPDATE `inventory_stock` SET `part_name`=?, `part_desc`=? WHERE `part_no`=?;";
      $cond = [$name, $desc, $part];
    }
    $pass = $this->exec($sql, $cond);

    // STOCK MOVEMENT - IF PART NUMBER IS CHANGED
    if ($pass && $part!=$newPart) {
      $sql = "UPDATE `inventory_movement` SET `part_no`=? WHERE `part_no`=?;";
      $cond = [$newPart, $part];
      $pass = $this->exec($sql, $cond);
    }

    // FINALIZE
    $this->end($pass);
    return $pass;
  }

  function del($part){
  // del() : delete part
  // PARAM $part : part number

    // MAIN ENTRY
    $this->start();
    $sql = "DELETE FROM `inventory_stock` WHERE `part_no`=?;";
    $cond = [$part];
    $pass = $this->exec($sql, $cond);

    // STOCK MOVEMENT - IF PART NUMBER IS CHANGED
    if ($pass) {
      $sql = "DELETE FROM `inventory_movement` WHERE `part_no`=?;";
      $pass = $this->exec($sql, $cond);
    }

    // FINALIZE
    $this->end($pass);
    return $pass;
  }
  
  function mvt($part, $type, $qty, $comment=null) {
  // mvt() : add new stock movement, update stock count
  // PARAM $part : part number
  //       $type : movement type - 'i'n, 'o'ut, or 's'tock take.
  //       $qty : quantity
  //       $comment : comment, if any

    // CHECKS
    // Invalid movement type
    if ($type!="i" && $type!="o" && $type!="s") {
      $this->error = "Invalid movement type";
      return false;
    }

    // Invalid quantity
    if (!is_numeric($qty)) {
      $this->error = "Invalid quantity";
      return false;
    }

    // Get current stock level - Invalid part number
    $current = $this->get($part);
    if ($current==false) {
      $this->error = "Invalid part number";
      return false;
    } else {
      $current = $current['part_stock'];
    }

    // INSERT MOVEMENT ENTRY
    $this->start();
    $sql = sprintf("INSERT INTO `inventory_movement` (`part_no`, `mvt_type`, `mvt_qty`%s) VALUES (?, ?, ?%s);",
      $comment ? ", `mvt_comment`" : "", 
      $comment ? ", ?" : ""
    );
    $cond = [$part, $type, $qty];
    if ($comment) { $cond[] = $comment; }
    $pass = $this->exec($sql, $cond);

    // UPDATE STOCK COUNT
    if ($pass) {
      $sql = "UPDATE `inventory_stock` SET `part_stock`=? WHERE `part_no`=?";
      switch ($type) {
        case "i":
          $current += $qty;
          break;
        case "o":
          $current -= $qty;
          break;
        case "s":
          $current = $qty;
          break;
      }
      $cond = [$current, $part];
      $pass = $this->exec($sql, $cond);
    }

    // THE RESULT
    $this->end($pass);
    return $pass;
  }
}
?>