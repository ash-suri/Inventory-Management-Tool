<?php
/* [INIT] */
require __DIR__ . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "2a-config.php";
require PATH_LIB . "2b-lib-inventory.php";
$invLib = new Inventory();

/* [AJAX REQUESTS] */
switch ($_POST['req']) {
  default:
    echo "INVALID REQUEST";
    break;

  /* [INTERFACE REQUESTS] */
  // LIST ALL PARTS
  // You might want to paginate this
  case "list":
    $parts = $invLib->getAll(); ?>
    <h1>MANAGE PARTS</h1>
    <input type='button' value='Add New Part' onclick="admin.addEdit();"/>
    <table class="zebra"><?php
      if (is_array($parts)) {
        foreach ($parts as $p) {
          printf("<tr><td class='i-stock'>%s</td>"
          . "<td><div class='i-name'>[%s] %s</div><div class='i-desc'>%s</div></td>"
          . "<td class='i-action'>"
          . "<input type='button' value='Delete' onclick=\"admin.del('%s');\"/>"
          . "<input type='button' value='Edit' onclick=\"admin.addEdit('%s');\"/>"
          . "<input type='button' value='Stock' onclick=\"stock.list('%s');\"/></td></tr>",
            $p['part_stock'],
            $p['part_no'], $p['part_name'], $p['part_desc'],
            $p['part_no'], $p['part_no'], $p['part_no']
          );
        }
      } else {
        echo "<tr><td>No parts found.</td></tr>";
      }
    ?></table>
    <?php break;

  // ADD/EDIT PART
  case "addEdit":
    $edit = isset($_POST['part']);
    if ($edit) {
      $part = $invLib->get($_POST['part']);
    } ?>
    <h1><?=$edit?"EDIT":"ADD"?> PART</h1>
    <form onsubmit="return admin.save();">
      <?php if ($edit) { ?>
      <input type="hidden" id="part_old" value="<?=$part['part_no']?>"/>
      <?php } ?>
      <label for="part_no">Part Number:</label>
      <input type="text" id="part_no" value="<?=$part['part_no']?>" required/>
      <label for="part_name">Part Name:</label>
      <input type="text" id="part_name" value="<?=$part['part_name']?>" required/>
      <label for="part_desc">Part Description:</label>
      <input type="text" id="part_desc" value="<?=$part['part_desc']?>"/>
      <?php if (!$edit) { ?>
      <label for="part_stock">Initial Stock:</label>
      <input type="text" id="part_stock" value="1" required/>
      <?php } ?>
      <br>
      <input type="button" value="Back" onclick="admin.list()"/>
      <input type="submit" value="Save"/>
    </form>
    <?php break;

  // SHOW STOCK MOVEMENT
  // You might want to paginate or limit this
  case "mvt-list":
    $part = $invLib->get($_POST['part']); 
    $movement = $invLib->getMvt($_POST['part']); ?>
    <h1>[<?=$part['part_no']?>] <?=$part['part_name']?></h1>
    <form id="mvt_form" onsubmit="return stock.add();">
      <input type="hidden" id="part_no" value="<?=$part['part_no']?>"/>
      <label for="mvt_type">Movement Type</label>
      <select id="mvt_type">
        <option value="i">IN</option>
        <option value="o">OUT</option>
        <option value="s">STOCK TAKE</option>
      </select>
      <label for="mvt_qty">Quantity</label>
      <input type="number" id="mvt_qty" value="1" required/>
      <label for="mvt_comment">Comment</label>
      <input type="text" id="mvt_comment"/>
      <br>
      <input type="button" value="Back" onclick="admin.list()"/>
      <input type="submit" value="Add Entry"/>
    </form>

    <table class="zebra"><?php
      if (is_array($movement)) {
        foreach ($movement as $m) {
          $qty = 0;
          $mtype = [
            "i" => "IN",
            "o" => "OUT",
            "s" => "STOCK TAKE"
          ];
          printf("<tr class='mvt-%s'><td class='mvt-stock'>%s</td><td><p>[%s] %s</p><span>%s</span></td></tr>",
            $m['mvt_type'], $m['mvt_qty'], $mtype[$m['mvt_type']], $m['mvt_date'], $m['mvt_comment']
          );
        }
      } else {
        echo "<tr><td>No stock movement found</td></tr>";
      }
    ?></table>
    <?php break;

  /* [PROCESS REQUESTS] */
  // ADD NEW PART
  case "add":
    echo $invLib->add($_POST['part'], $_POST['name'], $_POST['stock'], $_POST['desc']) ? "OK" : $invLib->error ;
    break;

  // UPDATE PART
  case "edit":
    echo $invLib->edit($_POST['oldPart'], $_POST['name'], $_POST['desc'], $_POST['part']) ? "OK" : $invLib->error ;
    break;

  // DELETE PART
  case "del":
    echo $invLib->del($_POST['part']) ? "OK" : $invLib->error ;
    break;
    
  // MOVEMENT - ADD NEW STOCK
  case "mvt":
    echo $invLib->mvt($_POST['part'], $_POST['type'], $_POST['stock'], $_POST['comment']) ? "OK" : $invLib->error ;
    break;
}
?>